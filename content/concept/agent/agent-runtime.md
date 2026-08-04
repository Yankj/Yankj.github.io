---
publish: true
---

# Agent 运行时：Skill 加载、Tool 调用与执行全过程

> 本文以 Career Agent 的真实代码为线索，完整拆解一次 Agent 运行从请求到响应的全过程——Skill 怎么加载、Tool 怎么注册、Agent Loop 怎么运转、模型怎么在推理中使用 Skill 和 Tool。

## 全景图

一次 Agent 运行的完整流程：

```
用户在前端选择 workflow 并发送消息
        │
        ▼
┌─ 请求阶段 ──────────────────────────────────────┐
│  1. 认证与 workspace 隔离                        │
│  2. 请求校验                                      │
│  3. 模型 Provider 初始化                          │
│  4. Skill 加载 → 拼装 system prompt              │
│  5. Tool 注册 → 准备 tools 数组                   │
│  6. 组装初始 messages                             │
└──────────────────────────────────────────────────┘
        │
        ▼
┌─ Agent Loop（最多 6 轮）─────────────────────────┐
│                                                   │
│  ┌─ 第 N 轮 ─────────────────────────────────┐   │
│  │                                            │   │
│  │  发送请求给 LLM                             │   │
│  │  messages: [system(skill), user, ...]      │   │
│  │  tools: [search_claims, read_career_...]   │   │
│  │                                            │   │
│  │  LLM 流式返回：                             │   │
│  │    ├── text_delta → 转发给前端              │   │
│  │    ├── tool_call → 累积参数                 │   │
│  │    └── completed → 本轮结束                 │   │
│  │                                            │   │
│  │  有 tool_call？                             │   │
│  │    ├── 是 → 执行工具 → 结果回传 → 继续循环  │   │
│  │    └── 否 → Agent 完成，关闭流              │   │
│  └────────────────────────────────────────────┘   │
└───────────────────────────────────────────────────┘
```

## 第一步：请求进入

前端发送一个 POST 请求到 `/api/agent/chat`，请求体包含：

```json
{
  "workflow": "career-intake",
  "messages": [
    { "role": "user", "content": "我上个月帮团队把 API 响应时间从 2 秒优化到了 200 毫秒" }
  ]
}
```

`workflow` 字段决定了本次对话的身份、Skill 和行为规则。`messages` 是对话历史。

## 第二步：认证与 workspace 隔离

```typescript
const supabase = await createClient();
const { data: { user } } = await supabase.auth.getUser();
if (!user) return Response.json({ error: "请先登录后使用 Agent。" }, { status: 401 });
```

Agent 首先验证用户身份，然后查找用户的 workspace：

```typescript
const membership = await supabase
  .from("workspace_members")
  .select("workspace_id")
  .eq("user_id", user.id)
  .limit(1)
  .maybeSingle();
```

如果没有 workspace，自动创建一个。这个 `workspaceId` 后续会传递给每个 Tool，确保所有数据操作都在当前 workspace 范围内——这是 AGENTS.md 中"多租户数据以 workspace 为边界"的实现。

## 第三步：Skill 加载

这是本次优化的核心。当用户选择 `workflow="career-intake"` 时，系统需要加载相关的 Skill。

### Skill 在文件系统中的组织

```
skills/
  ├── career-intake/
  │   ├── SKILL.md          ← SOP 指令
  │   └── agents/
  │       └── openai.yaml   ← 平台适配声明
  ├── audit-evidence/
  │   └── SKILL.md
  ├── analyze-job/
  │   └── SKILL.md
  └── ...（共 10 个 Skill）
```

### Workflow → Skill 映射

不是所有 Skill 都注入——只注入跟当前 workflow 相关的：

```typescript
const WORKFLOW_SKILLS: Record<Workflow, string[]> = {
  "career-intake":     ["career-intake", "audit-evidence"],
  "target-discovery":  ["analyze-job", "map-competencies"],
  "resume-strategy":   ["derive-resume", "validate-resume", "build-project-story"],
  "mock-interview":    ["generate-interview-drill", "interview-feedback-loop"],
};
```

用户选了 `career-intake` → 加载 `career-intake` 和 `audit-evidence` 两个 Skill。其他 8 个 Skill 不会出现在上下文中。

### 读取 SKILL.md

```typescript
function loadSkill(skillName: string): SkillContent | null {
  const filePath = join(SKILLS_DIR, skillName, "SKILL.md");
  const raw = readFileSync(filePath, "utf-8");
  return parseSkillFile(raw);
}
```

`parseSkillFile` 把 SKILL.md 拆成两部分：
- **YAML front matter**：`name` 和 `description`（用于索引）
- **Markdown 正文**：SOP 步骤和护栏规则（注入 prompt）

### 拼装 system prompt

```typescript
function buildSystemPrompt(workflow: Workflow): string {
  const skills = loadSkillsForWorkflow(workflow);

  return [
    WORKFLOW_IDENTITY[workflow],     // "你是 Career Agent 的职业探索助手..."
    "",
    "请严格遵循以下工作流规则：",
    "",
    skillSections,                    // SKILL.md 全文
    "",
    "通用规则：",
    COMMON_GUARDRAILS,                // workspace 隔离、Proposal 审批、状态词
    "",
    "工具使用指引：",
    TOOL_GUIDANCE,                    // 查证已有记录的提示
  ].join("\n");
}
```

最终拼出来的 system prompt 大致长这样：

```
你是 Career Agent 的职业探索助手。通过一次一个问题帮助用户回忆真实经历...

请严格遵循以下工作流规则：

--- Skill: career-intake ---
# Career intake

1. Read only the current workspace's career records and the supplied input.
2. Separate explicit fact, interpretation, target, missing detail, and contradiction.
3. Extract period, scope, role, action, result, metric definition, ownership, source.
4. Return a Claim proposal with conservative status; never write it directly.
5. Add unresolved details to the review queue.

Never infer dates, metrics, causality, titles, or personal ownership.

--- Skill: audit-evidence ---
# Audit evidence

1. Inspect the Claim, linked evidence, source versions, active downstream uses.
2. Verify source existence, status, period, scope, metric definition, ownership.
3. Detect conflicting dates, numbers, verbs, attribution, and maturity claims.
4. Propose exactly one outcome: retain, promote, downgrade, split, or block.
5. Return the evidence verdict, allowed wording, risk, and next evidence required.

Platform scale is not personal impact.

通用规则：
只在当前 workspace 作用域内读取和操作数据，不得跨 workspace 访问。
任何写入操作都是提案（Proposal），必须由用户明确确认后才能生效。
职业主张的状态词：FACT、FACT_PARTIAL、IN_PROGRESS、TARGET、HYPOTHESIS、PLACEHOLDER。
只有 FACT 和 FACT_PARTIAL 可以进入对外内容；FACT_PARTIAL 必须使用限定表达。

工具使用指引：
你可以使用提供的工具查询当前用户工作区中的职业数据。
当用户提到自己的经历、能力或目标时，先调用工具查证已有记录...
```

这段文本大约 800-1000 token，在 LLM 的上下文中占据了 system prompt 位置。

## 第四步：Tool 注册

Tools 的注册是静态的——不管哪个 workflow，当前都暴露同一组工具：

```typescript
export const agentTools: ModelTool[] = [
  {
    name: "search_claims",
    description: "搜索当前用户工作区中的职业主张（Claim）...",
    inputSchema: {
      type: "object",
      properties: {
        keyword: { type: "string", description: "搜索关键词" },
        status: { type: "string", enum: ["FACT", "FACT_PARTIAL", ...] },
      },
      required: ["keyword"],
    },
  },
  {
    name: "read_career_records",
    description: "读取当前用户工作区中的职业记录...",
    inputSchema: { ... },
  },
  {
    name: "get_target_jobs",
    description: "获取当前用户工作区中的目标岗位列表...",
    inputSchema: { ... },
  },
];
```

这些 Tool 的 schema 会被放在 API 请求的 `tools` 参数中，和 messages 一起发给 LLM。模型看到这些 schema 后，就知道自己可以调用哪些函数。

### Skill 和 Tool 在请求中的位置对比

```
一次 LLM API 请求的结构：

┌─────────────────────────────────────────────────┐
│ messages: [                                      │
│   { role: "system", content: "{Skill SOP}" },   │ ◄── Skill 在这里
│   { role: "user",   content: "用户的输入" },     │
│ ]                                                │
│                                                  │
│ tools: [                                         │ ◄── Tools 在这里
│   { name: "search_claims", ... },                │
│   { name: "read_career_records", ... },           │
│   { name: "get_target_jobs", ... },               │
│ ]                                                │
└─────────────────────────────────────────────────┘
```

**两者都在 LLM 生成第一个 token 之前就给到了。** 区别在于：
- Skill 在 `messages` 里，模型阅读它、内化它，推理时遵循它
- Tool 在 `tools` 参数里，模型看到 schema，自己决定要不要调用

## 第五步：组装消息历史

```typescript
const chatMessages: ModelMessage[] = [
  { role: "system", content: buildSystemPrompt(input.data.workflow as Workflow) },
  ...input.data.messages.map((m) =>
    m.role === "user"
      ? { role: "user" as const, content: m.content }
      : { role: "assistant" as const, content: m.content },
  ),
];
```

消息历史的结构是：

```
[
  { role: "system",    content: "{完整的 system prompt，含 Skill SOP}" },
  { role: "user",      content: "我上个月帮团队把 API 响应时间优化到了 200 毫秒" },
  // 如果有多轮对话历史，会继续追加...
]
```

## 第六步：Agent Loop

这是整个运行时的核心。Agent Loop 是一个最多 6 轮的循环：

```typescript
for (let iteration = 0; iteration < MAX_AGENT_ITERATIONS; iteration++) {
  // 1. 流式调用 LLM
  for await (const event of provider.stream({
    messages: agentMessages,
    tools: agentTools,
    signal: request.signal,
  })) {
    // 2. 处理流式事件
  }

  // 3. 如果没有 tool_call → 模型回复完成
  if (toolCallAccumulator.size === 0) {
    send({ type: "completed" });
    break;
  }

  // 4. 如果有 tool_call → 执行工具，结果回传，继续循环
  for (const tc of sortedToolCalls) {
    const result = await executeTool(toolName, args, toolCtx);
    agentMessages.push({ role: "tool", content: JSON.stringify(result), tool_call_id: tc.id });
  }
}
```

### 第 1 轮：模型第一次看到请求

LLM 收到：
- system prompt（含 `career-intake` 和 `audit-evidence` 的 SOP）
- 用户消息（"我上个月帮团队把 API 响应时间优化到了 200 毫秒"）
- tools 列表（3 个查询工具）

模型基于 Skill 的 SOP 进行推理：

> "career-intake 的规则说要分离事实和推断。用户说'帮团队'——这是团队协作还是个人主导？'200 毫秒'——这个数字的来源是什么？我需要先查一下有没有相关记录。"

模型输出：
```
text: "我来先查一下你工作区里有没有相关的记录。"
tool_call: search_claims({ keyword: "API 优化" })
```

### 应用代码执行工具

```typescript
const result = await executeTool("search_claims", { keyword: "API 优化" }, toolCtx);
```

`executeTool` 内部调用 `executeSearchClaims`，查询 Supabase：

```typescript
const { data, error } = await ctx.supabase
  .from("claims")
  .select("id, statement, status, period, scope, ownership, version, updated_at")
  .eq("workspace_id", ctx.workspaceId)  // ← workspace 隔离
  .ilike("statement", `%${keyword}%`)
  .order("updated_at", { ascending: false })
  .limit(10);
```

返回结果被序列化为 JSON，追加到消息历史：

```typescript
agentMessages.push({
  role: "tool",
  content: JSON.stringify({ count: 2, claims: [...] }),
  tool_call_id: tc.id,
});
```

### 第 2 轮：模型看到工具结果

LLM 现在的上下文：

```
messages: [
  { role: "system",    content: "{Skill SOP}" },
  { role: "user",      content: "我上个月帮团队把 API 响应时间优化到了 200 毫秒" },
  { role: "assistant", content: "我来先查一下...", tool_calls: [...] },
  { role: "tool",      content: '{"count": 2, "claims": [...]}' },
]
```

模型基于 Skill SOP + 工具结果继续推理：

> "查到了 2 条相关 Claim，但都是旧的。用户说的是'上个月'的新经历。按照 career-intake 的规则，我需要追问：这个优化是你个人主导的还是团队协作？'200 毫秒'这个数字是怎么测量的？"

模型输出（纯文本，没有 tool_call）：

```
"我查到了你之前有 2 条关于 API 优化的记录，但这次是新的经历。

想确认几个细节：
1. 你说的'帮团队'——你是主要负责人，还是参与者之一？
2. '从 2 秒到 200 毫秒'——这个数字是怎么测量的？是 P99 还是平均值？
3. 具体是哪个月？我需要记录准确的时间范围。"
```

没有 tool_call → `toolCallAccumulator.size === 0` → 发送 `completed` 事件 → Agent Loop 结束。

## 第七步：流式响应

整个过程中，模型的文本输出是流式的，通过 SSE（Server-Sent Events）实时推送给前端：

```
event: text_delta
data: {"delta":"我"}

event: text_delta
data: {"delta":"查到了"}

event: text_delta
data: {"delta":"你之前"}

...

event: tool_started
data: {"name":"search_claims"}

event: tool_finished
data: {"name":"search_claims"}

event: text_delta
data: {"delta":"想确认几个细节..."}

...

event: completed
data: {}
```

前端收到这些事件后，可以实时渲染文本流、显示工具调用状态、在完成时关闭加载状态。

## 完整的请求生命周期

把所有步骤串起来，一次完整的 Agent 运行时序：

```
时间轴
  │
  │  前端发起请求
  │     POST /api/agent/chat
  │     { workflow: "career-intake", messages: [...] }
  │
  │  ┌─ 服务端 ─────────────────────────────────────────┐
  │  │                                                   │
  │  │  1. 认证 → 获取 user                              │
  │  │  2. 查 workspace_members → 获取 workspaceId        │
  │  │  3. 校验请求体                                    │
  │  │  4. 初始化 AgnesProvider                          │
  │  │  5. buildSystemPrompt("career-intake")            │
  │  │     ├── 读取 skills/career-intake/SKILL.md        │
  │  │     ├── 读取 skills/audit-evidence/SKILL.md       │
  │  │     └── 拼装 system prompt                        │
  │  │  6. 组装 chatMessages                             │
  │  │                                                   │
  │  │  ┌─ Agent Loop 第 1 轮 ──────────────────────┐    │
  │  │  │  发送 LLM 请求 (messages + tools)          │    │
  │  │  │  流式接收：text_delta + tool_call           │    │
  │  │  │  执行 search_claims → 查 Supabase           │    │
  │  │  │  结果回传到 messages                        │    │
  │  │  └────────────────────────────────────────────┘    │
  │  │                                                   │
  │  │  ┌─ Agent Loop 第 2 轮 ──────────────────────┐    │
  │  │  │  发送 LLM 请求 (含工具结果的 messages)      │    │
  │  │  │  流式接收：text_delta                       │    │
  │  │  │  无 tool_call → completed                   │    │
  │  │  └────────────────────────────────────────────┘    │
  │  │                                                   │
  │  │  关闭 SSE 流                                      │
  │  └───────────────────────────────────────────────────┘
  │
  │  前端渲染完成
  │
  ▼
```

## 两种 Skill 加载模式的对比

Career Agent 用的是**静态映射**模式——用户选 workflow，代码直接读对应的 SKILL.md。还有一种**动态选择**模式——让 LLM 自己判断该加载哪个 Skill。

### 静态映射（Career Agent 当前用的）

```
用户选 workflow="career-intake"
    ↓
代码查映射表 → ["career-intake", "audit-evidence"]
    ↓
直接读文件 → 拼进 system prompt
    ↓
1 次 API 调用就能开始回答
```

优点：简单、快、token 效率高。
适合：workflow 明确、Skill 数量少。

### 动态选择（CatPaw / CrewAI 用的）

```
用户说"帮我优化简历"
    ↓
第 1 次 API 调用：只给 LLM 看 Skill 索引（name + description）
    ↓
LLM 判断："resume-tailor 这个 Skill 看起来相关"
    ↓
LLM 调用 read_file 工具加载 SKILL.md 全文
    ↓
第 2 次 API 调用：LLM 看到完整 SOP，按规则回答
```

优点：灵活，不需要用户显式选择。
适合：开放对话、Skill 数量多、无法预判用户意图。

### 关键区别

| | 静态映射 | 动态选择 |
|---|---|---|
| 谁决定加载哪个 Skill | 路由层（代码） | LLM（读 description 判断） |
| API 调用次数 | 1 次起 | 至少 2 次 |
| Skill 全文何时出现 | 请求发出前就拼好 | 作为 tool result 在第 2 轮出现 |
| 匹配方式 | 映射表 | LLM 语义理解 |
| 适合场景 | workflow 明确 | 开放对话 |

**Career Agent 未来从静态迁移到动态的信号**：当 Skill 数量超过 20 个、用户不再显式选 workflow、或者需要支持用户安装自定义 Skill 时。

## 总结

| 步骤 | 做什么 | 涉及的组件 |
|---|---|---|
| 认证 | 验证用户身份，获取 workspaceId | Supabase Auth |
| Skill 加载 | 按 workflow 读取 SKILL.md，拼装 system prompt | `lib/agent/skills.ts` |
| Tool 注册 | 准备 tools 数组（JSON Schema） | `lib/agent/tools.ts` |
| 消息组装 | system prompt + 对话历史 | `route.ts` |
| Agent Loop | 循环调用 LLM，处理 text/tool_call 事件 | `route.ts` + `agnes.ts` |
| Tool 执行 | 模型调用 Tool 时，服务端执行函数 | `lib/agent/tools.ts` + Supabase |
| 流式响应 | SSE 实时推送 text_delta / tool_started / completed | `route.ts` |

Skill 贯穿整个对话（在 system prompt 里），Tool 按需调用（在 Agent Loop 中）。两者在同一请求中协作：Skill 告诉模型"怎么想"，Tool 让模型"能做什么"。
