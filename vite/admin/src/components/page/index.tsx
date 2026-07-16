import { useState, useEffect, type KeyboardEvent } from "react";
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

  const selectTab = (nextKey: TabKey) => {
    setActive(nextKey);
    requestAnimationFrame(() => {
      document.getElementById(`mabox-page-tab-${nextKey}`)?.focus();
    });
  };

  const handleTabKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
    let nextIndex: number | null = null;
    if (event.key === "ArrowRight") nextIndex = (index + 1) % tabs.length;
    if (event.key === "ArrowLeft") nextIndex = (index - 1 + tabs.length) % tabs.length;
    if (event.key === "Home") nextIndex = 0;
    if (event.key === "End") nextIndex = tabs.length - 1;
    if (nextIndex === null) return;

    event.preventDefault();
    selectTab(tabs[nextIndex].key);
  };

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
      <div className="mabox-page-tabs" role="tablist" aria-label="内容与页面分组">
        {tabs.map((t, index) => (
          <button
            key={t.key}
            id={`mabox-page-tab-${t.key}`}
            type="button"
            role="tab"
            aria-controls="mabox-page-panel"
            aria-selected={active === t.key}
            tabIndex={active === t.key ? 0 : -1}
            className={`mabox-page-tab ${active === t.key ? "mabox-page-tab--active" : ""}`}
            onClick={() => setActive(t.key)}
            onKeyDown={(event) => handleTabKeyDown(event, index)}
          >
            {t.label}
          </button>
        ))}
      </div>
      <div
        id="mabox-page-panel"
        className="mabox-page-panel"
        role="tabpanel"
        aria-labelledby={`mabox-page-tab-${active}`}
      >
        {active === "aspect" && <Feature />}
        {active === "permission" && <Jurisdiction />}
        {active === "func" && <Function />}
        {active === "comment" && <Comment />}
      </div>
    </>
  );
};

export default App;
