import { fetchUiSchema, getUiSchemaSync } from "@/tool/uiSchema";
import { UiSchemaMap, UiSchemaEntry } from "@/tool/interface";
import { SearchItem, searchIndex } from "@/tool/featureIndexData";

const MODULE_TAB_MAP: Record<string, { tabKey: string; tabLabel: string }> = {
  page: { tabKey: "content", tabLabel: "内容与页面" },
  optimize: { tabKey: "site", tabLabel: "站点与媒体" },
  function: { tabKey: "seo", tabLabel: "SEO 与增强" },
  domestic: { tabKey: "china", tabLabel: "国内生态" },
  performance: { tabKey: "maintenance", tabLabel: "维护工具" },
};

const PRESET_TO_DISPLAY_TAG: Record<string, string | undefined> = {
  pure: undefined,
  blog: undefined,
  performance: "性能",
  security: "安全",
};

type FeatureRiskLevel = "none" | "low" | "high";

function parseRiskLevel(level: unknown): FeatureRiskLevel | null {
  if (level === "none" || level === "low" || level === "high") return level;
  return null;
}

function schemaToSearchItems(schema: UiSchemaMap): SearchItem[] {
  const items: SearchItem[] = [];
  for (const [id, entry] of Object.entries(schema)) {
    if (!entry.label && !entry.feature_id) continue;
    const moduleName = id.split("-")[0];
    const tabInfo = MODULE_TAB_MAP[moduleName];
    if (!tabInfo) continue;

    const displayTags = entry.risk_tags && entry.risk_tags.length > 0
      ? entry.risk_tags
      : (entry.preset_tags || [])
          .map((pt: string) => PRESET_TO_DISPLAY_TAG[pt])
          .filter((t: string | undefined): t is string => t !== undefined);

    items.push({
      id: entry.feature_id || id,
      label: entry.label || id,
      tabKey: tabInfo.tabKey,
      tabLabel: tabInfo.tabLabel,
      section: entry.group,
      tags: displayTags,
    });
  }
  return items;
}

let cachedIndex: SearchItem[] | null = null;

export function getFeatureIndexSync(): SearchItem[] {
  if (cachedIndex) return cachedIndex;

  const schema = getUiSchemaSync();
  if (schema) {
    cachedIndex = mergeIndex(schema);
    return cachedIndex;
  }
  return searchIndex;
}

export async function fetchFeatureIndex(): Promise<SearchItem[]> {
  const schema = await fetchUiSchema();
  if (schema) {
    cachedIndex = mergeIndex(schema);
    return cachedIndex;
  }
  return cachedIndex || searchIndex;
}

export function getFeatureLabelForPath(path: string): string | null {
  const schema = getUiSchemaSync();
  if (schema) {
    const schemaEntry = Object.values(schema).find((entry) => entry.path === path);
    if (schemaEntry?.label) return schemaEntry.label;
  }

  const featureId = path.split(".").join("-");
  const searchItem = getFeatureIndexSync().find(
    (item) => item.id === featureId || item.aliases?.includes(featureId),
  );
  return searchItem?.label || null;
}

export function getFeatureRiskLevelForPath(path: string): FeatureRiskLevel {
  const schema = getUiSchemaSync();
  if (schema) {
    const schemaEntry = Object.values(schema).find((entry) => entry.path === path);
    const schemaRiskLevel = parseRiskLevel(schemaEntry?.risk?.level);
    if (schemaRiskLevel) return schemaRiskLevel;
  }

  return "none";
}

function mergeIndex(schema: UiSchemaMap): SearchItem[] {
  const schemaItems = schemaToSearchItems(schema);
  if (schemaItems.length === 0) return searchIndex;

  const existingIds = new Set(searchIndex.map((i) => i.id));
  const merged = [...searchIndex];
  for (const item of schemaItems) {
    if (!existingIds.has(item.id)) {
      merged.push(item);
      existingIds.add(item.id);
    } else {
      const idx = merged.findIndex((m) => m.id === item.id);
      if (idx !== -1 && item.label) {
        merged[idx] = { ...merged[idx], label: item.label, section: item.section || merged[idx].section };
        if (item.tags && item.tags.length > 0) {
          merged[idx] = { ...merged[idx], tags: item.tags };
        }
      }
    }
  }
  return merged;
}

export function getSchemaEntry(featureId: string): UiSchemaEntry | null {
  const schema = getUiSchemaSync();
  if (!schema) return null;

  for (const [, entry] of Object.entries(schema)) {
    if (entry.feature_id === featureId) return entry;
  }
  if (schema[featureId]) return schema[featureId];
  return null;
}

export async function fetchSchemaEntry(featureId: string): Promise<UiSchemaEntry | null> {
  const schema = await fetchUiSchema();
  if (!schema) return null;

  for (const [, entry] of Object.entries(schema)) {
    if (entry.feature_id === featureId) return entry;
  }
  if (schema[featureId]) return schema[featureId];
  return null;
}

export { searchIndex as baseFeatureIndex };
