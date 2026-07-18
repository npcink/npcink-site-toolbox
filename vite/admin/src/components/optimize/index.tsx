import Site from "@/components/optimize/site";
import Medium from "@/components/optimize/medium";
import Other from "@/components/optimize/admin";
import { SettingsTabs, type SettingsTab } from "@/components/settings-ui";

interface OptimizeProps {
  targetItemId?: string;
}

const App: React.FC<OptimizeProps> = ({ targetItemId }) => {
  const tabs: SettingsTab[] = [
    { key: "site", label: "站点", prefixes: ["optimize-site-"], content: <Site /> },
    { key: "media", label: "媒体", prefixes: ["optimize-medium-"], content: <Medium /> },
    { key: "admin", label: "后台", prefixes: ["optimize-admin-"], content: <Other /> },
  ];

  return (
    <SettingsTabs
      ariaLabel="站点与媒体分组"
      idPrefix="mabox-site"
      tabs={tabs}
      targetItemId={targetItemId}
    />
  );
};

export default App;
