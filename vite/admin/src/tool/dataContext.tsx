import { createContext } from "react";

import { defaultVarOption } from "@/tool/defaultVar";
import {
  Option,
  SecretChange,
  SecretChanges,
  SecretPath,
  SecretStatus,
} from "@/tool/interface";
import { emptySecretStatus } from "@/tool/settingsSource";

export type SettingsLoadState = "loading" | "ready" | "error";

export interface OptionContextType {
  optionData: Option;
  updateOption: (father: string, son: string, newValue: unknown) => void;
  refreshOption: () => Promise<void>;
  discardChanges: () => void;
  lastSavedOption: Option;
  setLastSavedOption: (data: Option) => void;
  secretStatus: SecretStatus;
  secretChanges: SecretChanges;
  setSecretChange: (path: SecretPath, change?: SecretChange) => void;
  clearSecretChanges: () => void;
  settingsState: SettingsLoadState;
  settingsError: string | null;
  settingsRevision?: string;
  configEpoch: number;
}

export const DataContext = createContext<OptionContextType>({
  optionData: defaultVarOption,
  updateOption: () => {},
  refreshOption: async () => {},
  discardChanges: () => {},
  lastSavedOption: defaultVarOption,
  setLastSavedOption: () => {},
  secretStatus: emptySecretStatus(),
  secretChanges: {},
  setSecretChange: () => {},
  clearSecretChanges: () => {},
  settingsState: "loading",
  settingsError: null,
  settingsRevision: undefined,
  configEpoch: 0,
});
