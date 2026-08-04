# Yankj 的知识博客

一个用 Obsidian 记录、用 Quartz 发布、靠自动流水线同步的个人知识博客。

> **博客地址**：<https://yankj.github.io>

## 这是什么

这是我的个人博客，主要记录**学习过程中的一些思考**，以及**觉得值得分享的内容**。文章不一定成体系，但都是认真整理过的。

## 内容主题

领域比较广，主要可能涉及：

- **软件开发**：前端、后端、工程实践、软件交付范式
- **AI 与大模型**：Agent 开发、AI at Work、大模型应用与工具
- **互联网与产品**：对技术、行业和产品的一些观察与思考

主题不设限——学到什么、想到什么、觉得值得沉淀分享的，都会写在这里。

## 内容是怎么来的

文章大多来自我的个人知识库——平时在 Obsidian 里记录和整理，挑出值得分享的，再自动发布到这里：

```text
[在 Obsidian 写作整理] → [挑出值得分享的] → [自动同步] → [构建部署] → 你看到
```

写作和挑选由我来，剩下的同步、构建、部署全自动。

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