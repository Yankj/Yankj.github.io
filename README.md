# Yankj 的知识博客

一个用 Obsidian 记录、用 Quartz 发布、靠自动流水线同步的个人知识博客。

> **博客地址**：<https://yankj.github.io>

## 这是什么

这是我个人**学习与思考的沉淀**。日常我一边摸索、一边和 AI 结对探讨，在 Obsidian 知识库里把零散的想法整理成体系。博客发布的是其中**值得公开的精选部分**——不是流水账，而是我反复打磨过、愿意让别人看到的内容。

## 内容主题

博客的内容比较杂，但都围绕「**学习、思考与构建**」这条主线，主要包括：

- **AI 与 Agent**：和 AI 协作开发的体会、Agent 概念辨析（Skill vs Tool、运行时、Guardrail 等）、工具链选型
- **工程与工具**：真实项目里的踩坑与复盘、好用工具与库的使用心得、自动化实践
- **方法论**：知识管理、学习方式、如何把想法沉淀成可复用的体系

**不是所有知识都会变成博客。** 知识库是私有的、全量的，博客只收录我评估后值得公开的部分——这是刻意的筛选，而不是全量搬运。所有文章都是中文，尽量讲清楚「为什么」而不只是「怎么做」。

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