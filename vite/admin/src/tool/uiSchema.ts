import { settingsApi } from "@/api/index";
import { UiSchemaMap } from "@/tool/interface";

let cachedSchema: UiSchemaMap | null = null;
let fetchPromise: Promise<UiSchemaMap | null> | null = null;

export async function fetchUiSchema(): Promise<UiSchemaMap | null> {
  if (cachedSchema) return cachedSchema;
  if (fetchPromise) return fetchPromise;

  fetchPromise = settingsApi
    .getSchema()
    .then((response: any) => {
      const data = response?.data;
      if (data?.uiSchema && typeof data.uiSchema === "object") {
        cachedSchema = data.uiSchema as UiSchemaMap;
        return cachedSchema;
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
  return cachedSchema;
}
