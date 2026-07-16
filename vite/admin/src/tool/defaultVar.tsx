import settingsContract from "@/generated/settings-contract.json";
import type { Option } from "@/tool/interface";

export const defaultVarOption: Option = settingsContract.defaults;

export const defaultVarData = {
  url_site: "http://localhost:10029",
};
