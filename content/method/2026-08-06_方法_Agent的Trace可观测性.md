---
publish: true
title: "2026-08-06 Trace 可观测性：Agent 长任务卡住时，教你一步步排查"
date: "2026-08-06"
modified: "2026-08-06"
section: practice
knowledgeType: method
category: ai
tags: [agent, observability, tracing]
---

> Agent 在后台时，你完全看不见它在干什么。它调用了哪些工具、哪一步最慢、哪一轮的上下文撑爆了——全都是黑盒。如果任务跑了很久却卡住、变慢、结果不对，你从哪下手查？本文讲清 Trace（链路追踪）怎么把 Agent 的动态执行变成一棵可排查的调用树。

## 一个让你抓狂的场景

你发起了一个任务："检索我关于 Next.js 的笔记，生成一份 3 天复习计划。"

Agent 在后台自己跑了起来：先调用工具搜笔记，再调用另一个工具评估摘要，中间可能还触发了一次 Guardrail 重试……整个过程花了 8 秒。

8 秒不长，但**你不知道这 8 秒花在哪了**：

- 是 LLM 自己思考慢？
- 是工具查数据库慢？
- 还是某一轮的上下文（Token）爆炸，拖垮了生成速度？

如果是传统后端，你能靠日志一行行复现。但 Agent 的问题是——**它每次跑的路径都不一样，无法靠"重跑一遍"来复现**。

## 为什么 Agent 不能像传统后端那样调试

传统 Web 开发（普通 Next.js API）的执行链路是**确定性**的：代码一行行往下走，报错就复现，日志能对号入座。

Agent 的执行链路是**概率性、动态演变**的：

```
[User 输入]
   └─► [Step 1: LLM 思考] ──► 调用 Tool A (searchStudyNotes)
            └─► [Step 2: LLM 思考] ──► 调用 Tool B (evaluateNoteSummary)
                     └─► [Step 3: Guardrail 触发/重试]
                              └─► [Step 4: LLM 输出最终回答]
```

每一步都依赖前一步 LLM 的"即兴决定"，所以**同一请求每次走的路径都可能不同**。没有 Trace，你就只能对着一堆平铺的 `console.log` 猜——而猜，是最慢的排查方式。

## Trace 要盯的四大指标

| # | 指标 | 回答什么问题 |
|---|---|---|
| 1 | **链路树**（Execution Trace Tree） | 这次请求从 Request → Agent Loop → 各 Tool 的父子层级关系如何？ |
| 2 | **上下文快照**（Context Snapshot） | 每一轮 Loop 传给 LLM 的真实 `messages` 是什么？有没有被污染或超长？ |
| 3 | **Token & 成本归因** | 每一轮输入/输出消耗多少 Token、合成多少钱？谁是成本大户？ |
| 4 | **耗时 / Latency 拆解** | 慢在 LLM 首字延迟（TTFT），还是本地数据库 / API 工具？ |

这四类数据，就是排查长任务的"四张检查表"。

## 核心数据结构：Trace ID + Span ID + Parent Span ID

要把一整条动态执行串成**调用树（Tree/DAG）**，核心是"全局标识 + 父子层级"三件套：

- **Trace ID**：整个请求的全局唯一 ID，一次 run 一个。
- **Span ID**：每个子步骤（每次 LLM 请求、每次工具执行）自己的 ID。
- **Parent Span ID**：该步骤的父步骤 ID，用来拼出层级。

**为什么不能只打时间戳，也不能把 LLM 返回文本拼成一个大字符串存库？**
时间戳丢了结构（拼不出父子关系），长字符串丢了可读性（没法检索、没法按成本聚合）。只有 Trace ID + Span ID + Parent Span ID 才能组装成一棵可追溯、可聚合、可排查的树。

## 落地：用 OpenTelemetry 打点

业界已有标准化的落地方案——**OpenTelemetry（OTel）** 正在为 GenAI 定义统一语义约定，让任何 OTel 兼容后端（Langfuse、Jaeger、Grafana）都能一致地分析。核心打点就是 `tracer.startSpan()`：

```typescript
import { trace } from "@opentelemetry/api";
const tracer = trace.getTracer("agent");

// 一个 Agent run 是一个 Trace
const rootSpan = tracer.startSpan("agent.run", {
  attributes: { "task.kind": "study-plan" },
});

// 每次调用工具是一个子 Span，自动成为 rootSpan 的孩子
const toolSpan = tracer.startSpan("tool.call", {
  attributes: { "tool.name": "searchStudyNotes" },
});
// ...执行工具...
toolSpan.setAttribute("gen_ai.usage", tokenUsage);
toolSpan.end();

// 结束根 Span，整棵树就成型了
rootSpan.end();
```

**Langfuse** 这类 OTel-native 平台，能直接把这种嵌套 span 可视化成一棵 Agent 调用树——你一眼就能看到 8 秒到底耗在哪。

## Agent 可观测的独特挑战

传统监控只盯"延迟、Token、成功率"，对 Agent 不够，因为：

- **非确定性路径**：路径不预先可知，要动态呈现决策树和循环。
- **长时多轮**：任务可能跑几分钟到几小时、上百次 LLM 调用，需要会话级追踪而非单请求。
- **工具调用校验**：不仅要看 LLM 输出的文本，还要看它调了哪个工具、参数是什么、结果对不对。
- **错误级联传播**：一步推理或工具返回的小错，会跨多步放大。排查时得顺着整条因果链追，而不是只看最后一步。

## 总结

Agent 不能像传统后端那样"重跑一遍就复现"，所以你需要一双透视眼——**Trace**：

1. **四大指标**盯住：链路树、上下文快照、Token 成本、耗时拆解。
2. **Trace ID + Span ID + Parent Span ID** 把动态执行组装成可排查的调用树。
3. 用 **OTel 语义约定 + Langfuse** 之类的平台落地，把树可视化。

下一次你的 Agent 任务又卡了 8 秒，你要做的不是盯着进度条发呆，而是打开 Trace，看那棵树——卡在哪一步，一眼便知。

## 参考来源

- [OpenTelemetry](https://opentelemetry.io/) — 可观测性标准，GenAI 语义约定
- [Langfuse](https://langfuse.com/) — OTel-native 的 LLM/Agent 可观测平台
- [Jaeger](https://www.jaegertracing.io/) — 分布式链路追踪 UI
- [Grafana](https://grafana.com/) — 可观测性可视化平台
