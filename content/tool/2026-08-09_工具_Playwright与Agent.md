---
publish: true
---

# Playwright：Agent 为什么需要"手和眼"来操控浏览器

> 你可能注意到了：几乎每篇 Agent 开发的文章里都会提到 Playwright。一个浏览器自动化测试框架，为什么在 AI Agent 领域反复出现？这篇文章从 Puppeteer 到 Playwright 的演进讲起，拆解 Playwright 在 Agent 架构中扮演的角色——以及一个正在兴起的实践：用 Agent 驱动 Playwright 做自动化测试。

## Agent 什么都能做，就是上不了网

你做了一个 Agent：它能调用 OpenAI 的 API 做推理，能查 PostgreSQL 数据库做 RAG 检索，能发邮件、写文件。然后你对它说：

> "帮我去携程查一下明天北京到上海的最低机票价格。"

Agent 卡住了。它有"大脑"（LLM 推理），有"记忆"（数据库检索），但没有**眼睛**去看网页内容，也没有**手**去点击按钮、填写表单。

这就是 Playwright 出场的地方——它让 Agent 拥有操控真实浏览器的能力。在 Agent 的架构中，Playwright 就是那双"手和眼"。

但在此之前，得先回答一个更基本的问题：浏览器自动化工具那么多，为什么偏偏是 Playwright 成了 Agent 生态的事实标准？

## 从 Puppeteer 到 Playwright：主流为什么转了

### Puppeteer 的时代

2017 年，Google 发布了 Puppeteer——一个通过 Chrome DevTools Protocol（CDP）直接控制 Chrome 的 Node.js 库。它一出现就受到追捧，因为之前的方案（Selenium + WebDriver）太重了：要装浏览器驱动、要走 HTTP 协议跳转、速度慢、配置复杂。Puppeteer 直接连 CDP，又快又轻，迅速成为浏览器自动化和爬虫的首选。

但 Puppeteer 有一个从诞生就刻在基因里的限制：**只支持 Chrome**。Firefox、Safari 一概不支持。

### 核心团队跳到微软，造了 Playwright

2020 年，Puppeteer 的核心开发者从 Google 跳到微软，发布了 Playwright（业界广泛报道，见文末备注）。Playwright 不是 Puppeteer 的简单翻版——它解决了 Puppeteer 最重要的三个短板。

| 维度 | Puppeteer | Playwright |
|------|-----------|------------|
| **浏览器支持** | 仅 Chromium | Chromium + Firefox + WebKit（Safari 引擎） |
| **自动等待** | 无，需手写 `waitForSelector` 等 | 原生内置——元素就绪才操作 |
| **页面快照** | DOM 树 / 截图 | **无障碍树（Accessibility Tree / a11y snapshot）** |
| **多上下文隔离** | 较弱 | 原生 BrowserContext，隔离 cookie/session |
| **测试框架** | 需搭配 Jest / Mocha | 内置 `@playwright/test` |
| **语言支持** | Node.js 为主 | TypeScript、Python、Java、.NET 官方一等支持 |

这三个差异里，前两个（跨浏览器、自动等待）让 Playwright 在传统测试领域快速蚕食 Puppeteer 的份额。但真正让它在 Agent 时代"赢者通吃"的，是第三个：**无障碍树快照**。

### 为什么 a11y 快照改变了游戏

传统浏览器自动化获取页面信息的方式是读 DOM 树——一棵巨大的、充满 `<div>` 和嵌套标签的 HTML 结构。对人来说可读性极差，对 LLM 来说 Token 消耗巨大且充满噪音。

Playwright 提供了 [Accessibility API](https://playwright.dev/docs/api/class-accessibility)（`page.accessibility.snapshot()`），直接获取页面的**无障碍树**（Accessibility Tree）。这是一棵语义化的树，只保留对用户有意义的信息：

```
// DOM 方式（节选）—— 又长又乱
<div class="search-bar__container">
  <div class="input-wrapper">
    <input type="text" id="search-input" class="form-control" placeholder="搜索..." />
  </div>
  <button class="btn btn-primary search-btn" onclick="doSearch()">
    <span class="icon icon-search"></span>
    <span class="btn-text">搜索</span>
  </button>
</div>

// a11y 快照方式——干净、语义化
- textbox "搜索..." (ref: e5)
- button "搜索" (ref: e7)
```

a11y 快照把一坨 HTML 压缩成了"这里有个文本框叫'搜索'，这里有个按钮叫'搜索'"。这正是 LLM 理解页面最高效的方式——不需要 CSS class，不需要 DOM 层级，只需要**元素是什么、叫什么、能做什么**。

这个能力在 2020 年发布时，没多少人意识到它的价值。直到 2024 年 Agent 时代爆发，所有人突然发现：a11y 快照简直就是为 LLM 量身定做的页面描述格式。

## Playwright 在 Agent 架构中的位置

### 一张图看懂

```
┌─ Agent Loop ──────────────────────────────────┐
│                                               │
│  用户: "帮我查明天北京到上海的机票"             │
│       │                                       │
│       ▼                                       │
│  LLM 思考: 需要打开浏览器搜索航班               │
│       │                                       │
│       ▼                                       │
│  ┌─────────────────────────────────────┐      │
│  │ Tool: browser_navigate(url)         │ ◄── Playwright
│  │ Tool: browser_snapshot()            │ ◄── Playwright
│  │ → LLM 读到快照:                     │      │
│  │   e3: textbox "出发城市"             │      │
│  │   e5: textbox "到达城市"             │      │
│  │   e7: button "搜索"                 │      │
│  │ Tool: browser_fill(e3, "北京")      │ ◄── Playwright
│  │ Tool: browser_fill(e5, "上海")      │ ◄── Playwright
│  │ Tool: browser_click(e7)            │ ◄── Playwright
│  │ Tool: browser_snapshot()            │ ◄── Playwright
│  │ → LLM 读到搜索结果页快照             │      │
│  │ → 提取价格信息                       │      │
│  └─────────────────────────────────────┘      │
│       │                                       │
│       ▼                                       │
│  返回用户: "明天最低价 ¥520，东航 MU5103"      │
│                                               │
└───────────────────────────────────────────────┘
```

在你的 Agent 维度地图里，Playwright 属于 **D08 工具层**——它是一个特殊的 Tool：**Browser Tool**。和其他 Tool（查数据库、调 API）一样，它通过 function calling 暴露给 LLM，LLM 自主决定何时调用。

### 为什么 fetch / curl 不够用

有人会问：Agent 要读网页，用 `fetch` 抓 HTML 不就行了？

不行。现代 Web 页面大量使用 JavaScript 动态渲染，`fetch` 拿到的是一堆未执行的 `<script>` 标签，看不到实际内容。更别说需要登录态的页面、有反爬机制的网站、需要点击"加载更多"才能显示的列表。

**只有真实浏览器才能可靠地与 Web 交互**——这是 Playwright 不可替代的根本原因。

### 两种 Agent 操控浏览器的范式

目前 Agent 操控浏览器有两种主流方式，Playwright 都是底层引擎：

| 范式 | 怎么做 | 优势 | 劣势 | 代表 |
|------|--------|------|------|------|
| **快照驱动** | Playwright 获取 a11y 快照 → 文本喂给 LLM → LLM 输出"点击 e5" | 快、便宜（纯文本）、结构清晰 | 看不到 Canvas/图片/视频等非文本 UI | OpenAI CUA、Playwright CLI |
| **视觉驱动** | Playwright 截图 → 图片喂给多模态 LLM → LLM 输出坐标点击 | 像人一样"看"屏幕，不依赖 DOM | 慢、Token 成本高（每张图几百到几千 Token） | Anthropic Computer Use、Browser Use |

两种范式不是互斥的——成熟的项目通常混合使用：先看快照理解结构，遇到复杂视觉场景再截图辅助判断。

## Agent + Playwright 做自动化测试：一个正在兴起的实践

这是 Playwright 在 Agent 领域最让人兴奋的方向之一——不是让 Agent"用浏览器办事"，而是让 Agent**自己写测试、跑测试**。

### 传统自动化测试的痛点

写过 E2E 测试的人都知道，最大的痛苦是**脆弱性**：

```typescript
// 传统写法：依赖 CSS 选择器，一改 UI 就挂
await page.click('.search-bar__container > .btn-primary');
await expect(page.locator('.result-list .item:nth-child(1) .price')).toHaveText('¥520');
```

UI 改一个 class 名、调一下层级、换个布局，测试就挂了。团队花在"修测试"上的时间往往和写测试一样多。

### Agent 驱动的测试：理解意图，不依赖选择器

用 Agent + Playwright，思路完全变了。Agent 读 a11y 快照，理解页面语义，然后**基于意图**操作：

```
测试指令: "搜索北京到上海的机票，验证结果列表不为空"

Agent 执行:
1. snapshot() → 看到:
   - textbox "出发城市" (e3)
   - textbox "到达城市" (e5)
   - button "搜索" (e7)

2. fill(e3, "北京") → fill(e5, "上海") → click(e7)

3. snapshot() → 看到:
   - list "搜索结果"
     - listitem "东航 MU5103 ¥520"
     - listitem "国航 CA1831 ¥680"
     - listitem "南航 CZ6411 ¥590"

4. 断言: 结果列表包含 3 条，价格在合理范围 ✓
```

关键区别：Agent 不需要知道按钮的 class 是什么，它只需要知道"页面上有个叫'搜索'的按钮"。UI 改版了？class 名变了？没关系——只要按钮还叫"搜索"，Agent 就能找到它。

### 优势与当前局限

**优势**：

- **自愈性**：UI 变了但语义没变，Agent 仍然能操作——不脆
- **自然语言指令**：用"验证搜索结果不为空"代替一堆选择器断言，可读性高
- **自适应**：遇到弹窗、广告、Cookie 提示等意外元素，Agent 能自己处理，不会像脚本一样卡死

**当前局限**：

- **速度**：每一步都要调 LLM 推理，比纯脚本慢一个数量级
- **成本**：每次测试消耗 LLM Token，跑一次回归测试可能花费几美元
- **确定性**：LLM 是概率性的，同一个测试可能这次找到按钮、下次没找到——这在测试领域是大忌
- **复杂断言弱**：验证"价格降序排列"这种逻辑断言，Agent 不如显式代码可靠

因此，当前的实践趋势是**混合模式**：Agent 负责导航和交互（自愈、自适应），传统断言代码负责验证（确定性、精确）。代表项目包括 Browser Use、Stagehand（Browserbase 出品）等，都在探索这条路。

> 这个方向值得单独展开，后续可以专门写一篇"Agent 驱动的自动化测试实践"。

## 生态全景：谁在用 Playwright 做 Agent

| 项目 | 定位 | 与 Playwright 的关系 |
|------|------|---------------------|
| **OpenAI CUA** (Computer-Using Agent) | OpenAI 的"用电脑"Agent | 底层用 Playwright 驱动浏览器部分 |
| **Browser Use** | 开源 Web Agent 框架 | 基于 Playwright，封装成 Agent 可调用的 Tool |
| **Stagehand** (Browserbase) | AI 浏览器自动化框架 | 在 Playwright 之上加 AI 语义层 |
| **Skyvern** | 开源工作流自动化 Agent | 用 Playwright 做浏览器执行层 |
| **Anthropic Computer Use** | Claude 的"操控电脑"能力 | 截屏+点击方案，思路类似 |
| **Playwright MCP Server** | 微软官方 MCP Server | 把 Playwright 暴露为 MCP Tool，任何支持 MCP 的 Agent 都能调用 |

最后一行值得多说一句。**MCP（Model Context Protocol）** 是 Anthropic 提出的"Agent 工具协议"，正在成为 Agent 生态的连接标准。微软官方出了 Playwright MCP Server，意味着任何支持 MCP 的 Agent（包括 Claude、以及越来越多的开源 Agent 框架）都能直接调用 Playwright 操控浏览器——不需要自己写集成。

这是 Playwright 在 Agent 生态中"无处不在"的另一个结构性原因：它不只被某个框架绑定，而是通过 MCP 成为**跨框架的共享浏览器能力**。

## 总结

| 问题 | 答案 |
|------|------|
| Playwright 是什么 | 微软开发的跨浏览器自动化框架，驱动真实浏览器 |
| 为什么 Agent 需要 Playwright | Agent 的 Tool 只能调 API，但大量任务需要操控真实浏览器——Playwright 是那双"手和眼" |
| 为什么不是 Puppeteer | 跨浏览器支持 + 自动等待 + a11y 快照，三个差异让 Playwright 在 Agent 时代赢者通吃 |
| a11y 快照为什么关键 | 把 DOM 压缩成语义化的元素列表，是 LLM 理解页面最高效的格式 |
| Agent + 测试的新实践 | Agent 基于语义理解操作页面而非选择器，测试不再因 UI 改版而脆弱 |
| 生态地位 | OpenAI CUA、Browser Use、Playwright MCP Server 都以它为底座 |

一句话带走：**如果 Function Calling + Agent Loop 是 Agent 的大脑和心脏，Guardrail 是刹车，Trace 是透视眼，那么 Playwright 就是 Agent 的手和眼——让它能看见并操控真实的 Web 世界。**

## 参考来源

- [Playwright 官方文档](https://playwright.dev/docs/intro)
- [Playwright GitHub 仓库](https://github.com/microsoft/playwright)（70k+ stars，Apache 2.0）
- [Puppeteer GitHub 仓库](https://github.com/puppeteer/puppeteer)（Google 维护）
- [Playwright MCP Server](https://github.com/microsoft/playwright-mcp)（微软官方）
- [Browser Use](https://github.com/browser-use/browser-use)（开源 Web Agent 框架）
- [Stagehand](https://github.com/browserbase/stagehand)（Browserbase 出品）
- [Model Context Protocol (MCP)](https://modelcontextprotocol.io/)（Anthropic 提出）

> 注：文中「Puppeteer 核心开发者从 Google 跳到微软创建 Playwright」为业界广泛报道的说法，因网络受限未能联网核实原始出处，读者可自行查证。
