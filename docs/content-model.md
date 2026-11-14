# 博客内容模型

博客文章的分类采用四个互相独立的维度。同步源是 `kj-knowledge/publish/`，文章 frontmatter 会原样复制到博客，因此分类的唯一事实来源是知识库文章本身。

| 字段 | 可选值 | 解决的问题 | 示例 |
| --- | --- | --- | --- |
| `section` | `practice` / `knowledge` / `works` | 文章应该进入“实践”“知识”还是“作品”板块 | `practice` |
| `knowledgeType` | `concept` / `tool` / `method` / `insight` / `reading` / `work` | 文章的知识形态，延续现有概念、工具、方法分类；`work` 只用于作品 | `method` |
| `category` | 单个主题域 slug | 文章属于哪个主题域 | `ai`、`engineering`、`product` |
| `tags` | 3~7 个不重复标签 | 细粒度检索与关联 | `agent`、`observability` |
| `date` | `YYYY-MM-DD` | 首次公开发布日期，必须等于文件名日期 | `2026-08-09` |
| `modified` | `YYYY-MM-DD` | 最近一次公开内容修改日期 | `2026-08-09` |

## 现有分类如何结合

`概念 / 工具 / 方法` 不与“实践 / 知识”并列竞争，而是作为第二维度：

- `knowledge + concept`：解释一个概念、边界或机制，例如 RAG、Agent Runtime。
- `knowledge + tool`：介绍工具定位、能力与适用边界，例如 Playwright、Zod。
- `knowledge + method`：沉淀可迁移的方法和判断框架，例如数据库设计选型。
- `practice + method`：记录真实项目的实施方案、步骤、踩坑与复盘。
- `practice + tool`：记录工具在具体项目中的落地过程；不要因为用了某个工具就把文章写成工具说明书。

原来的 `publish/concept`、`publish/method`、`publish/tool` 路径继续保留，用于兼容已有 URL；新的 `section` 与 `knowledgeType` 负责产品层分类。不要把 `practice`、`knowledge`、`concept` 等再重复塞进 `tags`，标签只描述主题、技术和关键词。

## 推荐 frontmatter

```yaml
---
title: "2026-08-09 Agent Trace：从日志到可定位的运行链路"
publish: true
section: practice
knowledgeType: method
category: ai
tags: [agent, observability, tracing]
date: "2026-08-09"
modified: "2026-08-09"
---
```

文章正文的 H1 必须与 `title` 完全一致，并使用 `YYYY-MM-DD 原标题` 格式。Quartz 的文章页标题、HTML `<title>` 和社交分享标题都取 `title`；文件名日期不会自动注入页面标题。评论由博客站点统一配置，不在每篇文章里重复写开关。

Explorer 侧边栏也优先读取 frontmatter 的 `title`。当文章缺少 `title` 时，Quartz 会回退到文件名或 slug，因此可能出现同一列表中一部分文章显示 `2026-08-09_概念_...`，另一部分显示不带日期的自然语言标题。这个回退是故障暴露方式，不是兼容格式。

发布前必须通过 `npm run validate:content`，确保所有公开文章都具备日期前缀标题；不要通过修改 Explorer 样式或排序逻辑来掩盖缺失元数据。

公开文章文件名必须使用 `YYYY-MM-DD_类型_标题.md`，其中类型与 `knowledgeType` 对应：`概念`、`工具`、`方法`、`思考`、`阅读`。知识库内部节点不使用这条规则，继续采用稳定的语义文件名，避免双链因为日期变化而失效。

`date` 必须等于文件名日期，`modified` 不能早于 `date`。`整理日期`、`更新日期`仅作为知识库历史字段兼容；如果保留，必须分别与 `date`、`modified` 一致。

## 作品

作品（深度图文、行业报道、视频脚本等创作成品）来自 `kj-knowledge/publish/works/`，同步到 `content/works/`，列表页就是 Quartz 现有的文件夹页 `/works/`，首页 `content/index.md` 放一个入口链接。

- 文件名 `YYYY-MM-DD_作品_标题.md`，`section: works`、`knowledgeType: work`，三者必须一致。
- 在标准 8 个字段之后追加 `description`（必填，≤60 字，双引号单行字符串）和可选的 `cover`（封面图相对路径，如 `./assets/2026-11-14-ai-testing-cover.jpg`）。
- 图片放在 `works/assets/`，小写 kebab-case 文件名，png/jpg/jpeg/webp，单张 ≤3 MB；只有已发布作品引用到的图片会被同步。
- 封面由正文第一张图片 `![封面](./assets/...)` 显示，`cover` 必须同时在正文中嵌入。

## 迁移策略

存量公开文章已统一补齐 `title`、H1、`date`、`modified` 和分类字段；增量文章由知识库同步脚本和博客 CI 双重强校验。任何一层校验失败，都不得同步或部署。
