---
publish: true
---

# Skill 与 Tool：Agent 运行时中的两种能力

> 本文以 Career Agent 项目为实际案例，讲清楚 Skill 和 Tool 在 Agent 运行机制中的定位差异、各自的适用边界，以及如何判断一个能力应该封装成 Skill 还是 Tool。

## 一个常见的困惑

刚开始做 Agent 开发时，几乎所有人都会遇到同一个问题：

> "Skill 和 Tool 都能让 Agent 做事，它们到底有什么区别？我写的一段逻辑，什么时候该放在 SKILL.md 里，什么时候该写成 Tool 函数？"

这个困惑的根源在于：两者的边界不在"能不能执行代码"——Skill 可以带脚本，Tool 也可以调 LLM。真正的边界在更深的地方。

## 先看一个真实场景

用户对 Career Agent 说：

> "我上个月帮团队把 API 响应时间从 2 秒优化到了 200 毫秒"

Agent 需要做两件事：

1. **判断怎么处理这条信息**——这是事实还是推断？"个人做的"还是"团队协作"？数字"200 毫秒"可信吗？要不要追问来源？这些判断需要推理，不能靠 if-else。
2. **查一下已有记录**——用户之前有没有提过类似的优化经历？工作区里有没有相关的 Claim？

第一件事需要 **Skill**——一段注入到 system prompt 的 SOP 指令，告诉模型"怎么想"。第二件事需要 **Tool**——一个可调用的函数，去数据库里查一下。

## Skill 是什么

Skill 是一个**能力包**，核心是一份 `SKILL.md` 文件，包含：

- **YAML front matter**：`name` 和 `description`，用于索引和匹配
- **Markdown 正文**：SOP（标准操作流程）、步骤、护栏规则

以 Career Agent 中的 `career-intake` Skill 为例：

```markdown
---
name: career-intake
description: Capture a user's career history as workspace-scoped, auditable proposals.
---

# Career intake

1. Read only the current workspace's career records and the supplied input.
2. Separate explicit fact, interpretation, target, missing detail, and contradiction.
3. Extract period, scope, role, action, result, metric definition, ownership, source.
4. Return a Claim proposal with conservative status; never write it directly.
5. Add unresolved details to the review queue.

Never infer dates, metrics, causality, titles, or personal ownership.
```

这段文字被注入到 system prompt 后，模型在生成每一个 token 时都会"看到"它。它影响的是模型的**推理方式**——怎么拆分用户输入、怎么判断事实和推断、什么时候该追问。

### Skill 的本质特征

| 特征 | 说明 |
|---|---|
| **形态** | 自然语言文本（Markdown） |
| **注入位置** | `messages` 数组中的 system message |
| **模型如何使用** | 阅读并内化，影响推理过程 |
| **是否可执行** | 本身不执行——它是指令，不是代码（但可以附带脚本） |
| **触发方式** | 由路由层/编排层选择注入，不是模型在对话中自主调用 |
| **对推理的影响** | 直接影响——渗透到模型生成的每一个 token |

## Tool 是什么

Tool 是一个**可调用的函数**，通过 function calling 机制暴露给模型。它有：

- **JSON Schema**：定义函数名、参数、返回值
- **Executor**：服务端代码，实际执行操作

以 Career Agent 中的 `search_claims` Tool 为例：

```typescript
{
  name: "search_claims",
  description: "搜索当前用户工作区中的职业主张（Claim）。",
  inputSchema: {
    type: "object",
    properties: {
      keyword: { type: "string", description: "搜索关键词" },
      status: { type: "string", enum: ["FACT", "FACT_PARTIAL", ...] },
    },
    required: ["keyword"],
  },
}
```

模型看到这个 Tool 的 schema 后，可以自主决定何时调用、传什么参数。调用后，服务端执行查询，返回 JSON 数据，模型拿到结果继续推理。

### Tool 的本质特征

| 特征 | 说明 |
|---|---|
| **形态** | JSON Schema 声明 + 服务端 executor 函数 |
| **注入位置** | API 请求的 `tools` 参数 |
| **模型如何使用** | 判断是否需要调用，调用后拿结果继续推理 |
| **是否可执行** | 是——服务端代码执行（查数据库、调 API、写文件等） |
| **触发方式** | 模型自主决定（function calling） |
| **对推理的影响** | 间接——如果模型不调用，Tool 对推理零影响 |

## 核心差异：不是"确定性 vs 推理"，而是接口形态

很多人用"需不需要推理"来区分 Skill 和 Tool。这个启发法覆盖 90% 的情况，但有一个边界 case 会打破它：**Agent-as-Tool**。

你可以把一个完整的 Agent（内部有大量推理）包装成一个 Tool：

```python
reviewer_agent = Agent(name="Resume Reviewer", instructions="...", tools=[...])
manager = Agent(tools=[reviewer_agent.as_tool()])
```

这个 `reviewer_agent` 内部明明做了推理，但从 Manager 的角度看，它就是一个 Tool——传参数进去，拿结果出来。

所以"要不要推理"不是分界线。**真正的分界线是接口形态**：

> **Skill 是"注入到推理过程中的知识"——它塑造模型怎么想。**
> **Tool 是"可调用的黑箱"——不管里面是确定性脚本还是另一个 Agent，从调用方看就是 input → output。**

用一张表总结：

| 维度 | Skill | Tool |
|---|---|---|
| **接口** | 没有接口——它是文本 | 有接口——name + parameters + return |
| **透明度** | 透明——模型看到全部指令 | 不透明——模型不知道内部实现 |
| **在请求中的位置** | `messages` 里的 system message | `tools` 参数 |
| **模型怎么用** | 阅读并遵循 | 判断何时调用 |
| **触发者** | 路由层 | 模型自己 |
| **对推理的影响** | 直接——塑造推理方式 | 间接——只在被调用时提供数据 |

## 什么时候用 Skill

适合封装成 Skill 的能力有以下特征：

### 1. 需要指导推理方式

"把用户叙述拆分为事实/推断/目标/缺失/矛盾"——这不是一个函数调用能完成的。它需要模型在理解用户输入后做出判断，而且判断的方式有严格约束（不能推断日期、不能编造数字）。

### 2. 需要多轮对话

`career-intake` 不是一次性操作——它是一个"问一个问题 → 等用户回答 → 追问 → 整理"的过程。Tool 是一次调用返回一个结果，不适合表达多轮对话流程。

### 3. 需要护栏规则

"Never infer dates, metrics, causality, titles, or personal ownership"——这种禁止性规则需要贯穿整个对话，影响模型的每一次生成。Tool 的 JSON Schema 没法表达"推理方式"。

### 4. 需要产出领域语义的产物

Skill 的产出是 Proposal（提案）、审查裁定、简历版本——这些是有领域语义的结构化产物。Tool 的产出是原始数据（JSON 行、查询结果）。

## 什么时候用 Tool

适合封装成 Tool 的能力有以下特征：

### 1. 有明确的输入输出

`search_claims(keyword: string, status?: string) → Claim[]`——输入清晰，输出确定。模型不需要知道内部怎么查 Supabase，只需要知道传什么参数、返回什么结构。

### 2. 是原子操作

查一次数据库、调一次外部 API、写入一条记录——这些是最小不可分割的操作单元。模型可以在一轮对话中调用多个 Tool，也可以连续调用同一个 Tool。

### 3. 模型需要自主判断何时使用

模型看到用户说"我之前做过一个 API 优化项目"，自己判断"我应该查一下有没有相关记录"→ 调用 `search_claims`。这种"何时调用"的决策不适合硬编码在路由层。

### 4. 可以是读操作也可以是写操作

Tool 不限于查询。Career Agent 的 AGENTS.md 虽然当前只暴露了只读 Tool，但概念上 Tool 完全可以做写入、删除、创建——只是写操作需要走 Proposal 审批流程。

## 一个能力可以同时是 Skill 和 Tool

Skill 和 Tool 不是互斥的。一个能力可以同时以两种形态存在：

```
derive-resume (Skill)
  ├── SKILL.md          ← "按相关性筛选 Claim，每条 bullet 关联 Claim ID..."
  │                        注入 system prompt，指导模型怎么生成简历
  └── 通过 Tool 执行：
      ├── search_claims       ← 模型在对话中调用，查候选 Claim
      ├── read_career_records  ← 模型在对话中调用，查用户背景
      └── create_resume_proposal  ← 模型在对话中调用，创建简历提案（写操作）
```

Skill 的 SKILL.md 告诉模型"生成简历时应遵循什么规则"，Tool 提供模型"生成简历时能查什么、能写什么"。两者配合完成一次完整的简历生成流程。

## Skill 的脚本和 Tool 的边界

Skill 可以包含脚本（`scripts/` 目录），Tool 可以调用脚本。那脚本到底归 Skill 还是 Tool？

判断依据是**触发方式**：

| 脚本用途 | 归属 | 触发方式 |
|---|---|---|
| 编排层预运行（如准备候选数据） | Skill 包的私有脚本 | 路由层在进入 workflow 时调用 |
| 模型在对话中需要调用的能力 | 提取为独立 Tool | 模型通过 function calling 调用 |
| Skill 内部的辅助逻辑 | Skill 包的私有函数 | 不暴露给模型 |

核心原则：**如果脚本需要被模型在对话中自主调用，它就必须是 Tool。** 如果它只在编排层运行、模型不需要感知它的存在，它就留在 Skill 包内部。

## 行业参考

### CrewAI（56k stars）

CrewAI 在文档中用粗体写了一句：

> **Skills are NOT tools.** This is the most common point of confusion.

他们把 Agent 能力分为两大类：
- **Action Capabilities**（Tools、MCPs、Apps）：让 Agent 能"做事"
- **Context Capabilities**（Skills、Knowledge）：修改 Agent 的"提示词"

### OpenAI Agents SDK（28k stars）

没有 "Skill" 概念，但 `instructions` 参数起的就是 Skill 的作用。独特的 `Agent.as_tool()` 设计允许把一个 Agent（含 instructions + tools）包装成另一个 Agent 的 Tool。

### 共识

三个主流框架都**没有**把 Skill 合并进 Tool。它们是不同的层，解决不同的问题。

## 总结

| 问题 | 答案 |
|---|---|
| Skill 和 Tool 的本质区别 | Skill 是注入到推理中的知识（塑造怎么想），Tool 是可调用的黑箱（提供能做什么） |
| 什么适合做 Skill | 需要推理判断、多轮对话、护栏规则、领域语义产物的能力 |
| 什么适合做 Tool | 有明确输入输出、原子操作、模型需自主判断何时使用的能力 |
| 两者关系 | 互补——Skill 指导推理，Tool 提供能力，一个能力可以同时以两种形态存在 |
| 能不能只用一种 | 不能——只有 Skill 没有 Tool，模型无法查询和写入数据；只有 Tool 没有 Skill，模型不知道遵循什么规则 |
