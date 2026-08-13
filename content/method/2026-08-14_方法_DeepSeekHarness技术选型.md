---
publish: true
title: "2026-08-14 DeepSeek Harness 技术选型：最小外部依赖与最大化自主掌控"
date: "2026-08-14"
modified: "2026-08-14"
section: knowledge
knowledgeType: method
category: ai
tags: [deepseek-harness, agent-runtime, tech-stack, cordis, dependency-management]
---

> 一个 Agent 框架到底是「站在巨人的肩膀上」还是「自主掌控」，看它的依赖清单就能判断。DeepSeek Harness 给出的答案是：框架内核 vendor 进仓库、核心循环 100% 自研、外部依赖只出现在可替换的 Provider 边界。

## 一个最核心的问题：Agent 框架有没有第三方依赖？

大多数 Agent 项目的技术选型，第一件事是选一个框架——LangChain、AutoGen、Semantic Kernel，或者 OpenAI Agents SDK。DeepSeek Harness 的选择完全不同：**它没有选任何一个 Agent 框架，而是把一个插件框架 Cordis 的源码完整 vendor 进了自己的仓库。**

Cordis 是开源社区项目（[cordiverse/cordis](https://github.com/cordiverse/cordis)，作者 [Shigma](https://github.com/shigma)），提供 Context、Fiber、Events、Registry 等插件框架原语。DeepSeek Harness 对它的处理不是「npm install」，而是四步手术：

1. **重命名 scope**：`cordis` → `@deepseek-ai/cordis`，`@cordisjs/plugin-*` → `@deepseek-ai/cordis-plugin-*`
2. **标记 private**：不独立发布
3. **本地修改 18 处**：fiber 生命周期加固、Loader 事务性配置、HMR 精确监听、Include 补丁语义等
4. **pin 到特定 commit**：每个 vendored 包记录上游仓库与 commit hash

所有 harness 包声明 `@deepseek-ai/cordis` 为 peerDependency——框架层随 harness 一起发布，消费者拿到的是经过审计和加固的版本。

## 三个层次：框架层、核心层、外围层

把整个依赖清单摊开，会得到三层清晰的分工：

| 层次 | 组成 | 依赖性质 |
|------|------|----------|
| 框架层 | Cordis + Cosmokit + Schemastery + Loader | vendored（workspace 内部包） |
| 核心 Agent 逻辑 | agent-loop、session、tools、system-prompt、scope | 100% 自主实现 |
| 外围能力 | E2B、OpenTelemetry、ACP、Codex SDK | 领域专用 SDK，Provider 边界 |

核心 Agent 逻辑——Agent 循环、事件溯源 Session、工具执行管道、系统提示词组装、Scope 作用域——全部是 dsh 独有的代码，不依赖任何外部 Agent 框架。

## 关键自主实现：把「核心」牢牢攥在手里

以下能力完全自主实现，不依赖第三方库：

| 自主实现 | 说明 |
|----------|------|
| Agent 循环（agent-loop） | turn/step 驱动，无框架依赖 |
| 事件溯源 Session | append-only log + surface 投影 + deriveMessages |
| 工具执行管道 | pre-execute → guard → execute → post-execute 瀑布流 |
| 系统提示词组装 | section 收集 + tool schema 组装 |
| SQLite 持久化 | 用 Node.js 22 内置 node:sqlite，无外部绑定 |
| Landlock 沙箱 | C 语言原生实现（native addon） |

## 选型哲学：五条原则

整个项目的技术选型，可以归纳为「最小外部依赖 + 最大化自主掌控」：

1. **能用 Node.js 内置的不用外部包**：`node:sqlite` 替代 `better-sqlite3`，`node:fs/promises` 替代各种 fs 库。
2. **能 vendor 的不 npm 依赖**：Cordis 框架全家桶。
3. **能自写的不引库**：Landlock 沙箱用 C 自己写，Scope 作用域零依赖。
4. **外部依赖只在 Provider 边界**：E2B SDK、OTel SDK、ACP SDK 都在可替换的能力缝末端。
5. **类型系统极度严格**：TypeScript 6.0 + strict + noImplicitAny + exactOptionalPropertyTypes + noUncheckedIndexedAccess。

## 开发工具链一览

| 工具 | 用途 |
|------|------|
| TypeScript 6.0 | 类型系统 |
| tsx | 源码直接执行 |
| tsdown | 运行时打包（tsc 出类型，tsdown 出 lib） |
| vitest | 测试 + 覆盖率 |
| oxlint | Rust 写的 Linter |
| Vite + React 18 | 前端构建 |
| pnpm workspace | 包管理 |
| knip / publint / jscpd | 死代码 / 发布 / 重复检测 |

## 这个选型带来了什么

核心（框架 + 循环 + 会话 + 工具 + 沙箱）完全可审计、可修改、可掌控；外部依赖仅存在于可替换的外围能力提供者中。想改 Agent 循环就改 agent-loop，想换沙箱就换 sandbox Provider，想加新模型就加一个 LLM adapter——不需要和任何上游框架的 API 撕扯。

## 边界：什么时候不该这么选

「能 vendor 不 npm 依赖」是有代价的——上游 Cordis 的 bug 修复和安全更新需要手动同步，18 处本地修改意味着每次上游更新都要做合并。如果你的团队没有长期维护 vendored 代码的意愿和能力，直接用 npm 依赖一个主流框架反而更务实。这条路线适合对运行时掌控力有强需求的团队。

## 一句话带走

DeepSeek Harness 的选型不是「零依赖」，而是「把依赖分层」：框架 vendor 进仓库、核心自研、外部 SDK 只出现在可替换的 Provider 边界。

## 参考来源

- [cordiverse/cordis](https://github.com/cordiverse/cordis) — vendored 的插件框架上游
- [deepseek-ai/deepseek-harness](https://github.com/deepseek-ai/deepseek-harness) — 本文分析对象
