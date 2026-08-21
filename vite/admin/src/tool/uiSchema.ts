import { settingsApi } from "@/api/index";
import { UiSchemaMap } from "@/tool/interface";
import { __ } from "@/tool/i18n";
import settingsContract from "@/generated/settings-contract.json";

function isRiskLevel(level: string): level is "none" | "low" | "high" {
  return level === "none" || level === "low" || level === "high";
}

const generatedSchema: UiSchemaMap = Object.fromEntries(
  Object.entries(settingsContract.uiSchema).map(([id, entry]) => {
    const level = entry.risk.level;
    if (!isRiskLevel(level)) {
      throw new Error(`Invalid generated UI risk level for ${id}`);
    }
    return [id, { ...entry, risk: { ...entry.risk, level } }];
  }),
);
let cachedServerSchema: UiSchemaMap | null = null;
let fetchPromise: Promise<UiSchemaMap | null> | null = null;

function localizeEntry(entry: UiSchemaMap[string]): UiSchemaMap[string] {
  return {
    ...entry,
    label: entry.label ? __(entry.label) : entry.label,
    group: entry.group ? __(entry.group) : entry.group,
    risk_tags: entry.risk_tags?.map((tag) => __(tag)),
    risk: entry.risk
      ? {
          ...entry.risk,
          title: entry.risk.title ? __(entry.risk.title) : entry.risk.title,
          warning: entry.risk.warning ? __(entry.risk.warning) : entry.risk.warning,
          suggestion: entry.risk.suggestion ? __(entry.risk.suggestion) : entry.risk.suggestion,
        }
      : entry.risk,
  };
}

function localizeSchema(schema: UiSchemaMap): UiSchemaMap {
  return Object.fromEntries(Object.entries(schema).map(([id, entry]) => [id, localizeEntry(entry)]));
}

function mergeWithGeneratedSchema(serverSchema: UiSchemaMap | null): UiSchemaMap {
  if (!serverSchema) return localizeSchema(generatedSchema);

  const merged: UiSchemaMap = localizeSchema(generatedSchema);
  for (const [id, entry] of Object.entries(serverSchema)) {
    merged[id] = localizeEntry({ ...generatedSchema[id], ...entry });
  }
  return merged;
}

export async function fetchUiSchema(): Promise<UiSchemaMap | null> {
  if (cachedServerSchema) return mergeWithGeneratedSchema(cachedServerSchema);
  if (fetchPromise) return fetchPromise;

  fetchPromise = settingsApi
    .getSchema()
    .then((response: any) => {
      const data = response?.data;
      if (data?.uiSchema && typeof data.uiSchema === "object") {
        cachedServerSchema = data.uiSchema as UiSchemaMap;
        return mergeWithGeneratedSchema(cachedServerSchema);
      }
      return null;
    })
    .catch(() => null)
    .finally(() => {
      fetchPromise = null;
    });

  return fetchPromise;
}

export function getUiSchemaSync(): UiSchemaMap | null {
  return mergeWithGeneratedSchema(cachedServerSchema);
}

export function hasFetchedUiSchemaSync(): boolean {
  return cachedServerSchema !== null;
}
