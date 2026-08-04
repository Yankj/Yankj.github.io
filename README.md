# Yankj 的知识博客

一个用 Obsidian 记录、用 Quartz 发布、靠自动流水线同步的个人知识博客。

> **博客地址**：<https://yankj.github.io>

## 这是什么

这是我个人知识沉淀的**精选输出**。日常我在 Obsidian 知识库里持续记录和思考，把其中有价值、经过整理的部分提炼成「精品笔记」，公开发布到这里。这里不是流水账，而是我反复打磨过、愿意让别人看到的内容。

## 内容主题

博客围绕「**用 AI 构建软件**」这条主线展开，主要包括：

- **Agent 概念**：Skill 与 Tool 的分界、Agent 运行时、Guardrail / Trace / Handoff 等
- **工程实践**：工具链选型、真实项目里的踩坑与复盘
- **方法论**：知识管理、自动化流水线、如何把沉淀变成公开输出

所有文章都是中文，尽量讲清楚「为什么」而不只是「怎么做」。

## 内容是怎么来的

每篇文章都来自我的 Obsidian 知识库 `kj-knowledge`，发布链路全自动：

```
在 Obsidian 写作 → 标记 publish: true → push 到知识库
  → 自动同步到本站 content/
  → Quartz 构建 → 部署到 GitHub Pages → 你在浏览器看到
```

我只负责**写**和**标记**，剩下的同步、构建、部署全部交给流水线，不需要任何手动操作。

---

## 技术备注（面向开发者）

- **生成器**：[Quartz](https://github.com/jackyzha0/quartz) v5，Obsidian 原生支持 wikilink / callout
- **主题**：macOS（[quartz-themes](https://github.com/saberzero1/quartz-themes)）
- **托管**：GitHub Pages（Actions 部署模式）
- **自动化**：两条 GitHub Actions 流水线——`sync-to-blog`（从知识库同步）与 `deploy`（构建部署）
- **`content/index.md`**：手动维护的精选首页，不会被同步流水线覆盖

### 本地预览

```bash
npm install
npx quartz build --serve
```