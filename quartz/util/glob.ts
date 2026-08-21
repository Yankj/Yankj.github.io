import path from "path"
import { FilePath } from "./path"
import { globby } from "globby"

export function toPosixPath(fp: string): string {
  return fp.split(path.sep).join("/")
}

export async function glob(
  pattern: string,
  cwd: string,
  ignorePatterns: string[],
): Promise<FilePath[]> {
  const fps = (
    await globby(pattern, {
      cwd,
      ignore: ignorePatterns,
      gitignore: true,
      // Quartz's own discovery patterns do not need brace expansion. Keep it
      // disabled so a future config change cannot turn file discovery into an
      // unbounded in-memory expansion.
      braceExpansion: false,
    })
  ).map(toPosixPath)
  return fps as FilePath[]
}
