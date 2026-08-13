---
publish: true
title: "2026-08-14 Plugin、Tool 与 Skill：Agent 能力粒度的三种本质"
date: "2026-08-14"
modified: "2026-08-14"
section: knowledge
knowledgeType: concept
category: ai
tags: [plugin, tool, skill, agent, capability-model]
---

> 在 Agent 语境里，Plugin、Tool、Skill 三个词经常被混用。DeepSeek Harness 用一句话把它们分开了：Plugin 是「谁提供能力」，Tool 是「做什么」，Skill 是「怎么做」。

## 三个概念的精确边界

| 概念 | 定义 | 本质 |
|------|------|------|
| Plugin | 框架基本组成单元，注册可逆效果 | 代码组织与生命周期的原子单位 |
| Tool | 注册在 ctx.tools 的函数，模型可调用 | 模型可调用的原子能力（RPC 接口） |
| Skill | 可复用任务指令（Markdown + frontmatter） | 模型按需加载的指令集，不执行代码 |

## 最大的混淆：Plugin 和 Tool 是什么关系？

行业里（LangChain、OpenAI Assistants），「tool」和「plugin」经常混用——一个 plugin 通常就是一个 tool。但在 DeepSeek Harness 里，**Plugin 是所有行为的载体，Tool 只是 Plugin 注册的一种效果**。

一个 Plugin 可以同时注册：

- 工具（`ctx.tools.register()`）
- 提示词段（`ctx.systemPrompt.add()`）
- 事件监听器（`ctx.on('agent/pre-step', ...)`）
- 能力提供方（`ctx.shell.registerProvider()`）
- Skill 提供方（`ctx.skills.registerProvider()`）
- 以上任意组合

所以 `dsh-tool-bash` 这个「插件」注册了 `bash` 这个「工具」——插件是代码单元，工具是它暴露给模型的接口。

## Tool 和 Skill：不同层次的东西

| 维度 | Tool | Skill |
|------|------|-------|
| 本质 | 可执行的函数 | 可阅读的指令文本 |
| 模型如何使用 | 调用 → 参数验证 → 执行 → 返回结果 | 调用 → 加载正文 → 阅读 → 按指令行动 |
| 返回内容 | 结构化数据（JSON + 内容块） | `<skill_content>` XML 指令块 |
| 执行副作用 | 有（文件、Shell、网络） | 无（只返回文本） |
| 加载时机 | schema 常驻请求 | 目录摘要常驻，正文按需加载 |

Skill 本身通过一个 Tool（`skill` 工具）被模型加载——这个 Tool 由 `dsh-tool-skill` 插件注册，消费 `ctx.skills` 注册表，注册表由 `dsh-skill` 插件提供。

## 与行业概念对照

- **vs OpenAI Function Calling**：dsh 的 Tool 概念基本一致，但多了多阶段执行管道、并发调度、scope 影子覆盖、UI 展示意图。
- **vs Claude Code 的 Skill**：完全一致，dsh 直接兼容 `.agents/skills/` 目录格式。
- **vs LangChain 的 Tool**：概念等价，但 LangChain 没有 Plugin 层次，工具本身就是扩展单元。
- **vs OpenAI Assistants 的 Plugin**：早期 plugin 是「工具包」（一组 API endpoint），dsh 的 Plugin 是框架级代码组织单元，不只限于工具。

## 一条食物链

```
Plugin → 注册 Skill Registry → 被 skill 工具（Tool）→ 被 Agent Loop 调用 → 模型决定使用
```

每一层都是独立的可替换单元。理解这个分层，就能在扩展 Agent 时做出正确判断：新增「能力」写 Plugin 注册 Tool，新增「知识/流程」写 Skill，新增「思路」写提示词段。

## 一句话带走

Plugin 是「谁提供能力」，Tool 是「做什么」，Skill 是「怎么做」——一个 Plugin 可以注册任意数量的 Tool、Skill 提供方、事件监听器和提示词段。

## 参考来源

- [cordiverse/cordis](https://github.com/cordiverse/cordis) — Plugin 框架
- [deepseek-ai/deepseek-harness](https://github.com/deepseek-ai/deepseek-harness) — 本文分析对象
