# Yankj.github.io

基于 [Quartz](https://github.com/jackyzha0/quartz) 的个人知识博客。

- `content/` 存放已发布的精品笔记，由 `kj-knowledge` 仓库的同步流水线自动推送（只有 `publish: true` 的笔记会进入这里）。
- `content/index.md` 是手动维护的首页，不会被同步流水线覆盖。
- 推送 `main` 分支会自动触发 GitHub Pages 部署。

## 本地预览

```bash
npm install
npx quartz build --serve
```