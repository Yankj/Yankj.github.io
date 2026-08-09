# 统计与评论启用说明

## 阅读量与访问量

Quartz 已内置 analytics 注入能力，不再额外维护一套计数 API。建议第一阶段使用 GoatCounter：它提供页面浏览、来源、设备等基础统计，适合 GitHub Pages 的静态站点；如果后续需要事件分析，再切换到 Umami 或 PostHog。

在 `quartz.config.default.yaml` 中填入站点信息后启用：

```yaml
configuration:
  analytics:
    provider: goatcounter
    websiteId: your-site-code
```

指标口径建议固定为：

- `pageview`：一次页面加载/路由访问；用于文章阅读量。
- `visits`：分析服务按会话窗口聚合的访问次数；用于访问量。
- 不在 Markdown 中写死阅读数，也不在 GitHub Actions 中累计文件计数，避免静态站点出现竞态和伪造数据。

Quartz 开启了 SPA 导航，启用 analytics 后要用本地预览和线上实际跳转验证：首次打开、站内点击、浏览器前进后退各应只产生符合服务商口径的事件。

## 留言评论

仓库已经安装 `@quartz-community/comments`，推荐使用 Giscus。评论存储在 GitHub Discussions，作者可在 GitHub 上审核，博客不需要自建数据库或 API。

启用步骤：

1. 在评论承载仓库打开 Discussions，并安装 giscus app。
2. 打开 `giscus.app`，选择仓库和 Discussions 分类，复制 `repoId`、`categoryId`。
3. 将 `quartz.config.default.yaml` 中 comments 的 `enabled` 改为 `true`，填入 `repo`、`repoId`、`category`、`categoryId`，并保留 `mapping: pathname`。
4. 部署后分别从文章页和暗色模式验证加载、登录授权、评论发布、回复和审核。

Giscus 的页面映射必须保持 `pathname`，这样评论会跟文章 URL 绑定；若以后调整 URL，需要先做重定向或迁移映射，避免评论分裂。评论组件不应在首页加载，当前布局仅放在文章正文之后。

## 发布前检查

```bash
npm run validate:content
npx quartz build --serve
```

先保持未配置凭据时的功能关闭状态；Analytics 和 Giscus 的 ID 不应写入知识库 Skill 或公开的示例文件。
