import { settingsApi } from "@/api/index";
import { UiSchemaMap } from "@/tool/interface";
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

function mergeWithGeneratedSchema(serverSchema: UiSchemaMap | null): UiSchemaMap {
  if (!serverSchema) return generatedSchema;

  const merged: UiSchemaMap = { ...generatedSchema };
  for (const [id, entry] of Object.entries(serverSchema)) {
    merged[id] = { ...generatedSchema[id], ...entry };
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
