import settingsContract from "@/generated/settings-contract.json";
import { __ } from "@/tool/i18n";
import { fetchUiSchema, getUiSchemaSync } from "@/tool/uiSchema";
import type { UiSchemaEntry } from "@/tool/interface";

export interface SearchItem {
  id: string;
  label: string;
  tabKey: string;
  tabLabel: string;
  section?: string;
  keywords?: string[];
  tags?: string[];
  aliases?: string[];
}

export const searchIndex: SearchItem[] = settingsContract.searchIndex.map((item) => ({
  ...item,
  label: __(item.label),
  tabLabel: __(item.tabLabel),
  section: item.section ? __(item.section) : item.section,
  keywords: item.keywords?.map((keyword) => __(keyword)),
  tags: item.tags?.map((tag) => __(tag)),
  aliases: item.aliases?.map((alias) => __(alias)),
}));
export const baseFeatureIndex = searchIndex;

type FeatureRiskLevel = "none" | "low" | "high";

function parseRiskLevel(level: unknown): FeatureRiskLevel | null {
  if (level === "none" || level === "low" || level === "high") return level;
  return null;
}

export function getFeatureIndexSync(): SearchItem[] {
  return searchIndex;
}

export function fetchFeatureIndex(): Promise<SearchItem[]> {
  return Promise.resolve(searchIndex);
}

export function getFeatureLabelForPath(path: string): string | null {
  const schema = getUiSchemaSync();
  if (schema) {
    const schemaEntry = Object.values(schema).find((entry) => entry.path === path);
    if (schemaEntry?.label) return schemaEntry.label;
  }

  const featureId = path.split(".").join("-");
  const searchItem = searchIndex.find(
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
