# 博客内容模型

博客文章的分类采用四个互相独立的维度。同步源是 `kj-knowledge/publish/`，文章 frontmatter 会原样复制到博客，因此分类的唯一事实来源是知识库文章本身。

| 字段 | 可选值 | 解决的问题 | 示例 |
| --- | --- | --- | --- |
| `section` | `practice` / `knowledge` | 文章应该进入“实践”还是“知识”板块 | `practice` |
| `knowledgeType` | `concept` / `tool` / `method` / `insight` / `reading` | 文章的知识形态，延续现有概念、工具、方法分类 | `method` |
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

## 迁移策略

存量公开文章已统一补齐 `title`、H1、`date`、`modified` 和分类字段；增量文章由知识库同步脚本和博客 CI 双重强校验。任何一层校验失败，都不得同步或部署。
