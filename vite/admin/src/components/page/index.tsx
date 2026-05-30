import { useState, useEffect } from "react";
import Comment from "@/components/page/comment";
import Feature from "@/components/page/feature";
import Function from "@/components/page/function";
import Jurisdiction from "@/components/page/jurisdiction";

const tabs = [
  { key: "aspect", label: "外观", prefixes: ["page-feature-"] },
  { key: "permission", label: "权限", prefixes: ["page-jurisdiction-"] },
  { key: "func", label: "功能", prefixes: ["page-function-"] },
  { key: "comment", label: "评论", prefixes: ["page-comment-"] },
] as const;

type TabKey = (typeof tabs)[number]["key"];

interface PageProps {
  targetItemId?: string;
}

const App: React.FC<PageProps> = ({ targetItemId }) => {
  const [active, setActive] = useState<TabKey>("aspect");

  useEffect(() => {
    if (!targetItemId) return;
    const matchedTab = tabs.find((t) =>
      t.prefixes.some((prefix) => targetItemId.startsWith(prefix))
    );
    if (matchedTab) {
      setActive(matchedTab.key);
    }
  }, [targetItemId]);

  return (
    <>
      <div className="mabox-page-tabs">
        {tabs.map((t) => (
          <button
            key={t.key}
            className={`mabox-page-tab ${active === t.key ? "mabox-page-tab--active" : ""}`}
            onClick={() => setActive(t.key)}
          >
            {t.label}
          </button>
        ))}
      </div>
      <div className="mabox-page-panel">
        {active === "aspect" && <Feature />}
        {active === "permission" && <Jurisdiction />}
        {active === "func" && <Function />}
        {active === "comment" && <Comment />}
      </div>
    </>
  );
};

export default App;
