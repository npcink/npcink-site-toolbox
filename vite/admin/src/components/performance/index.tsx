import Oss from "@/components/performance/oss";
import SeoChecker from "@/components/performance/seo_checker";
import MediaHealth from "@/components/performance/media_health";
import SearchEnhance from "@/components/performance/search_enhance";
import DbClean from "@/components/performance/db_clean";
import { SettingsTabs, type SettingsTab } from "@/components/settings-ui";

interface PerformanceProps {
  targetItemId?: string;
}

const App: React.FC<PerformanceProps> = ({ targetItemId }) => {
  const tabs: SettingsTab[] = [
    { key: "storage", label: "对象存储", prefixes: ["performance-oss-"], content: <Oss /> },
    { key: "seo", label: "SEO 检查", prefixes: ["performance-seo_checker-"], content: <SeoChecker /> },
    { key: "media", label: "媒体体检", prefixes: ["performance-media_health-"], content: <MediaHealth /> },
    { key: "search", label: "搜索增强", prefixes: ["performance-search_enhance-"], content: <SearchEnhance /> },
    { key: "database", label: "数据库", prefixes: ["performance-db_clean-"], content: <DbClean /> },
  ];

  return (
    <SettingsTabs
      ariaLabel="维护工具分组"
      idPrefix="mabox-maintenance"
      tabs={tabs}
      targetItemId={targetItemId}
    />
  );
};

export default App;
