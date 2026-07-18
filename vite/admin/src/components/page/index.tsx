import Comment from "@/components/page/comment";
import Feature from "@/components/page/feature";
import Function from "@/components/page/function";
import Jurisdiction from "@/components/page/jurisdiction";
import { SettingsTabs, type SettingsTab } from "@/components/settings-ui";

interface PageProps {
  targetItemId?: string;
}

const App: React.FC<PageProps> = ({ targetItemId }) => {
  const tabs: SettingsTab[] = [
    { key: "aspect", label: "外观", prefixes: ["page-feature-"], content: <Feature /> },
    { key: "permission", label: "权限", prefixes: ["page-jurisdiction-"], content: <Jurisdiction /> },
    { key: "func", label: "功能", prefixes: ["page-function-"], content: <Function /> },
    { key: "comment", label: "评论", prefixes: ["page-comment-"], content: <Comment /> },
  ];

  return (
    <SettingsTabs
      ariaLabel="内容与页面分组"
      idPrefix="mabox-content"
      tabs={tabs}
      targetItemId={targetItemId}
    />
  );
};

export default App;
