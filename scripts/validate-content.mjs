#!/usr/bin/env node

import { basename, relative, resolve } from "node:path"
import { readdirSync, readFileSync, statSync } from "node:fs"

const root = resolve(process.argv[2] ?? "content")
const compatibility = process.argv.includes("--compat")
const allowedSections = new Set(["practice", "knowledge"])
const allowedKnowledgeTypes = new Set(["concept", "tool", "method", "insight", "reading"])
const fileTypeByKnowledgeType = new Map([
  ["concept", "概念"],
  ["tool", "工具"],
  ["method", "方法"],
  ["insight", "思考"],
  ["reading", "阅读"],
])
const isoDate = /^\d{4}-\d{2}-\d{2}$/
const slug = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

function walk(dir) {
  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const path = resolve(dir, entry.name)
    if (entry.isDirectory()) return walk(path)
    return entry.name.endsWith(".md") ? [path] : []
  })
}

function readDocument(path) {
  const source = readFileSync(path, "utf8")
  if (!source.startsWith("---\n")) return { frontmatter: null, source }
  const end = source.indexOf("\n---", 4)
  if (end === -1) return { frontmatter: null, source }
  return { frontmatter: source.slice(4, end), source }
}

function fieldValue(frontmatter, field) {
  const match = frontmatter.match(new RegExp(`^${field}\\s*:\\s*(.+)$`, "m"))
  return match?.[1].trim().replace(/^['"]|['"]$/g, "").replaceAll('\\"', '"') ?? ""
}

function parseInlineList(value, field, relativePath) {
  if (!value.startsWith("[") || !value.endsWith("]")) {
    error(errors, `${relativePath}: ${field} must be an inline YAML list`)
    return []
  }
  return value
    .slice(1, -1)
    .split(",")
    .map((item) => item.trim().replace(/^['"]|['"]$/g, ""))
    .filter(Boolean)
}

function error(errors, message) {
  errors.push(message)
}

const errors = []
const warnings = []

for (const path of walk(root)) {
  const relativePath = relative(process.cwd(), path)
  const { frontmatter, source } = readDocument(path)
  if (!frontmatter || fieldValue(frontmatter, "publish") !== "true") continue

  const fileName = basename(path)
  const fileNameMatch = fileName.match(/^(\d{4}-\d{2}-\d{2})_(概念|工具|方法|思考|阅读)_(.+)\.md$/)
  if (!fileNameMatch) {
    error(errors, `${relativePath}: published article filename must be YYYY-MM-DD_类型_标题.md`)
    continue
  }

  const publishedDate = fileNameMatch[1]
  const requiredFields = ["title", "section", "knowledgeType", "category", "tags", "date", "modified"]
  for (const field of requiredFields) {
    if (!fieldValue(frontmatter, field)) {
      const message = `${relativePath}: published content is missing ${field}`
      ;(compatibility ? warnings : errors).push(message)
    }
  }

  const title = fieldValue(frontmatter, "title")
  const h1 = source.match(/^#\s+(.+)$/m)?.[1].trim()
  if (!h1) error(errors, `${relativePath}: published article must contain an H1 title`)
  if (title && h1 && title !== h1) error(errors, `${relativePath}: frontmatter title must match the H1 title`)
  if (title && !title.startsWith(`${publishedDate} `)) {
    error(errors, `${relativePath}: rendered title must start with '${publishedDate} '`)
  }

  const section = fieldValue(frontmatter, "section")
  if (section && !allowedSections.has(section)) error(errors, `${relativePath}: section must be practice or knowledge`)
  const knowledgeType = fieldValue(frontmatter, "knowledgeType")
  if (knowledgeType && !allowedKnowledgeTypes.has(knowledgeType)) {
    error(errors, `${relativePath}: knowledgeType is not supported`)
  }
  if (knowledgeType && fileTypeByKnowledgeType.get(knowledgeType) !== fileNameMatch[2]) {
    error(errors, `${relativePath}: filename type ${fileNameMatch[2]} does not match knowledgeType ${knowledgeType}`)
  }

  const category = fieldValue(frontmatter, "category")
  if (category && !slug.test(category)) error(errors, `${relativePath}: category must be a lowercase slug`)

  const tags = parseInlineList(fieldValue(frontmatter, "tags"), "tags", relativePath)
  if (tags.length < 3 || tags.length > 7) error(errors, `${relativePath}: tags must contain 3-7 items`)
  if (new Set(tags.map((tag) => tag.toLowerCase())).size !== tags.length) {
    error(errors, `${relativePath}: tags must be unique`)
  }
  const reserved = new Set([section, knowledgeType, fileNameMatch[2], "practice", "knowledge"])
  if (tags.some((tag) => reserved.has(tag.toLowerCase()))) {
    error(errors, `${relativePath}: tags must not duplicate section or knowledge type`)
  }

  const date = fieldValue(frontmatter, "date")
  const modified = fieldValue(frontmatter, "modified")
  if (!isoDate.test(date) || date !== publishedDate) {
    error(errors, `${relativePath}: date must equal the filename date ${publishedDate}`)
  }
  if (!isoDate.test(modified) || modified < date) {
    error(errors, `${relativePath}: modified must be YYYY-MM-DD and not earlier than date`)
  }
  if (fieldValue(frontmatter, "整理日期") && fieldValue(frontmatter, "整理日期") !== date) {
    error(errors, `${relativePath}: 整理日期 must match date when present`)
  }
  if (fieldValue(frontmatter, "更新日期") && fieldValue(frontmatter, "更新日期") !== modified) {
    error(errors, `${relativePath}: 更新日期 must match modified when present`)
  }
}

for (const message of warnings) console.warn(`WARN ${message}`)
for (const message of errors) console.error(`ERROR ${message}`)

console.log(`Validated published content under ${root} (${compatibility ? "compatibility" : "strict"} mode).`)
if (errors.length > 0) process.exitCode = 1
