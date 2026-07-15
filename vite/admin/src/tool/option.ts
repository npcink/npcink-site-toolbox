import { defaultVarOption } from "@/tool/defaultVar";
import { Option, SECRET_PATHS } from "@/tool/interface";

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

const STRING_ARRAY_PATHS = new Set(["page.function.countdown"]);
const NUMBER_ARRAY_PATHS = new Set([
  "page.jurisdiction.category_id",
  "page.jurisdiction.tag_id",
  "page.jurisdiction.page_id",
  "page.jurisdiction.single_id",
]);

function assertExactKeys(
  value: Record<string, unknown>,
  expected: Record<string, unknown>,
  path: string,
): void {
  const expectedKeys = Object.keys(expected);
  const actualKeys = Object.keys(value);

  for (const key of expectedKeys) {
    if (!Object.prototype.hasOwnProperty.call(value, key)) {
      throw new Error(`设置缺少字段：${path ? `${path}.` : ""}${key}`);
    }
  }
  for (const key of actualKeys) {
    if (!Object.prototype.hasOwnProperty.call(expected, key)) {
      throw new Error(`设置包含未知字段：${path ? `${path}.` : ""}${key}`);
    }
  }
}

function assertBatchReplacePairs(value: unknown[], path: string): void {
  value.forEach((item, index) => {
    if (!isRecord(item)) {
      throw new Error(`设置字段类型错误：${path}.${index}`);
    }
    const expected = { find: "", replace: "" };
    assertExactKeys(item, expected, `${path}.${index}`);
    if (typeof item.find !== "string" || typeof item.replace !== "string") {
      throw new Error(`设置字段类型错误：${path}.${index}`);
    }
  });
}

function assertArrayValue(value: unknown, path: string): void {
  if (!Array.isArray(value)) {
    throw new Error(`设置字段类型错误：${path}`);
  }

  if (STRING_ARRAY_PATHS.has(path)) {
    if (!value.every((item) => typeof item === "string")) {
      throw new Error(`设置字段类型错误：${path}`);
    }
    return;
  }
  if (NUMBER_ARRAY_PATHS.has(path)) {
    if (!value.every((item) => typeof item === "number" && Number.isFinite(item))) {
      throw new Error(`设置字段类型错误：${path}`);
    }
    return;
  }
  if (path === "page.function.batch_replace_pairs") {
    assertBatchReplacePairs(value, path);
    return;
  }

  throw new Error(`未定义的设置数组契约：${path}`);
}

function assertValueMatchesTemplate(value: unknown, template: unknown, path: string): void {
  if (Array.isArray(template)) {
    assertArrayValue(value, path);
    return;
  }
  if (isRecord(template)) {
    if (!isRecord(value)) {
      throw new Error(`设置字段类型错误：${path || "settings"}`);
    }
    assertExactKeys(value, template, path);
    Object.keys(template).forEach((key) => {
      assertValueMatchesTemplate(
        value[key],
        template[key],
        path ? `${path}.${key}` : key,
      );
    });
    return;
  }

  if (typeof template === "number") {
    if (typeof value !== "number" || !Number.isFinite(value)) {
      throw new Error(`设置字段类型错误：${path}`);
    }
    return;
  }
  if (typeof value !== typeof template) {
    throw new Error(`设置字段类型错误：${path}`);
  }
}

function assertContainsNoSecretKeys(value: Record<string, unknown>): void {
  for (const path of SECRET_PATHS) {
    const parts = path.split(".");
    let current: unknown = value;
    for (let index = 0; index < parts.length; index += 1) {
      if (!isRecord(current) || !Object.prototype.hasOwnProperty.call(current, parts[index])) {
        break;
      }
      if (index === parts.length - 1) {
        throw new Error(`设置数据包含敏感字段：${path}`);
      }
      current = current[parts[index]];
    }
  }
}

export function assertValidOption(value: unknown): asserts value is Option {
  if (!isRecord(value)) {
    throw new Error("设置数据格式无效");
  }

  assertContainsNoSecretKeys(value);
  assertValueMatchesTemplate(value, defaultVarOption, "");
}

export function updateOptionValue(
  previous: Option,
  father: string,
  son: string,
  newValue: unknown,
): Option {
  const previousFather = isRecord(previous[father]) ? previous[father] : {};

  return {
    ...previous,
    [father]: {
      ...previousFather,
      [son]: newValue,
    },
  };
}
