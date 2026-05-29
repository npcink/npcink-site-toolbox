import React from "react";
import { useState, lazy, Suspense, useEffect, useCallback } from "react";
import { Affix, Spin, Dropdown, Button } from "antd";
import { QuestionCircleOutlined } from "@ant-design/icons";

import { defaultOption, DataContext, fetchSettings } from "@/tool/dataContext";
import Save from "@/tool/save";
import FeatureSearch from "@/components/feature-search";

const Dashboard = lazy(() => import("@/components/dashboard/index"));
const Page = lazy(() => import("@/components/page/index"));
const Optimize = lazy(() => import("@/components/optimize/index"));
const Login = lazy(() => import("@/components/login/index"));
const Function = lazy(() => import("@/components/function/index"));
const Domestic = lazy(() => import("@/components/domestic/index"));
const Performance = lazy(() => import("@/components/performance/index"));
const AiReview = lazy(() => import("@/components/ai_review/index"));

const About = lazy(() => import("@/components/about/index"));

const TabFallback = (
  <div style={{ display: "flex", justifyContent: "center", padding: "48px" }}>
    <Spin size="large" />
  </div>
);

interface NavItem {
  key: string;
  label: string;
  icon: string;
  component: React.LazyExoticComponent<React.FC<any>>;
  props?: Record<string, any>;
}

interface NavGroup {
  groupLabel: string;
  items: NavItem[];
}

const navGroups: NavGroup[] = [
  {
    groupLabel: "概览",
    items: [
      { key: "0", label: "仪表盘", icon: "dashicons-dashboard", component: Dashboard },
    ],
  },
  {
    groupLabel: "内容与页面",
    items: [
      { key: "1", label: "页面", icon: "dashicons-admin-page", component: Page },

    ],
  },
  {
    groupLabel: "站点增强",
    items: [
      { key: "2", label: "优化", icon: "dashicons-admin-tools", component: Optimize },
      { key: "3", label: "登录页", icon: "dashicons-lock", component: Login },
      { key: "11", label: "性能优化", icon: "dashicons-performance", component: Performance },
    ],
  },
  {
    groupLabel: "AI 与生态",
    items: [
      { key: "5", label: "功能", icon: "dashicons-admin-plugins", component: Function },
      { key: "10", label: "国内生态", icon: "dashicons-location-alt", component: Domestic },
      { key: "12", label: "AI 审核", icon: "dashicons-shield", component: AiReview },
    ],
  },
];

const helpItems = [

  { key: "9", label: "关于", icon: "dashicons-info", component: About },
];

const allNavItems: NavItem[] = [
  ...navGroups.flatMap((g) => g.items),
  ...helpItems,
];

const App: React.FC = () => {
  const [optionData, setOptionData] = useState(defaultOption);
  const [lastSavedOption, setLastSavedOption] = useState(defaultOption);
  const [isMobile, setIsMobile] = useState(window.innerWidth < 768);
  const [activeTab, setActiveTab] = useState("0");
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth < 768);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  useEffect(() => {
    fetchSettings().then((data) => {
      setOptionData(data);
      setLastSavedOption(data);
    });
  }, []);

  const updateOption = (father: string, son: string, newValue: any) => {
    setOptionData((prevOptionData) => {
      const updatedOptionData = { ...prevOptionData };
      if (!updatedOptionData[father]) {
        updatedOptionData[father] = {};
      }
      updatedOptionData[father][son] = newValue;
      return updatedOptionData;
    });
  };

  const refreshOption = async () => {
    const freshData = await fetchSettings();
    setOptionData(freshData);
    setLastSavedOption(freshData);
  };

  const handleSearchNavigate = useCallback((tabKey: string, itemId: string) => {
    setActiveTab(tabKey);
    setMobileMenuOpen(false);
    setTimeout(() => {
      const el = document.getElementById(itemId);
      if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
        el.style.transition = "background 0.3s";
        el.style.background = "#e6f4ff";
        setTimeout(() => { el.style.background = ""; }, 2000);
      }
    }, 100);
  }, []);

  const activeNavItem = allNavItems.find((item) => item.key === activeTab);
  const activeGroup = navGroups.find((g) => g.items.some((i) => i.key === activeTab));

  const handleNavClick = (key: string) => {
    setActiveTab(key);
    setMobileMenuOpen(false);
  };

  const helpDropdownItems = {
    items: helpItems.map((item) => ({
      key: item.key,
      label: (
        <span style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <span className={`dashicons ${item.icon}`} style={{ fontSize: 16, width: 16, height: 16, lineHeight: "16px" }} />
          {item.label}
        </span>
      ),
    })),
    onClick: ({ key }: { key: string }) => {
      setActiveTab(key);
    },
  };

  const renderContent = () => {
    const item = allNavItems.find((i) => i.key === activeTab);
    if (!item) return null;
    const Comp = item.component;
    const extraProps = item.key === "0" ? { onNavigate: handleSearchNavigate } : {};
    return (
      <Suspense fallback={TabFallback}>
        <Comp {...extraProps} />
      </Suspense>
    );
  };

  const breadcrumbs = [
    "魔法工具箱",
    activeGroup ? activeGroup.groupLabel : "帮助",
    activeNavItem?.label || "",
  ];

  return (
    <>
      <DataContext.Provider value={{ optionData, updateOption, refreshOption, lastSavedOption, setLastSavedOption }}>
        <div className="mabox-shell">
          <Affix offsetTop={32}>
            <header className="mabox-header">
              <div className="mabox-header-left">
                <span className="dashicons dashicons-admin-generic mabox-header-icon" />
                <h1 className="mabox-header-title">
                  魔法工具箱
                  <small className="mabox-header-subtitle">
                    <a target="_blank" href="https://www.npc.ink">For Npcink</a>
                  </small>
                </h1>
              </div>
              {!isMobile && (
                <div className="mabox-header-center">
                  <FeatureSearch onNavigate={handleSearchNavigate} style={{ maxWidth: 280 }} />
                </div>
              )}
              <div className="mabox-header-right">
                <Dropdown menu={helpDropdownItems} placement="bottomRight">
                  <Button type="text" icon={<QuestionCircleOutlined />} className="mabox-help-btn">
                    帮助
                  </Button>
                </Dropdown>
                {!isMobile && <Save />}
              </div>
            </header>
          </Affix>

          <div className="mabox-body">
            {isMobile && (
              <div className="mabox-mobile-search">
                <FeatureSearch onNavigate={handleSearchNavigate} style={{ maxWidth: "100%" }} />
              </div>
            )}
            {isMobile && (
              <button
                className="mabox-mobile-toggle"
                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              >
                <span className="dashicons dashicons-menu" />
                {activeNavItem?.label || "导航"}
              </button>
            )}

            {isMobile && mobileMenuOpen && (
              <div className="mabox-mobile-nav-overlay" onClick={() => setMobileMenuOpen(false)} />
            )}

            <aside className={`mabox-sidebar ${isMobile && mobileMenuOpen ? "mabox-sidebar--open" : ""}`}>
              {navGroups.map((group) => (
                <div className="mabox-nav-group" key={group.groupLabel}>
                  <div className="mabox-nav-group-label">{group.groupLabel}</div>
                  {group.items.map((item) => (
                    <div
                      key={item.key}
                      className={`mabox-nav-item ${activeTab === item.key ? "mabox-nav-item--active" : ""}`}
                      onClick={() => handleNavClick(item.key)}
                    >
                      <span className={`dashicons ${item.icon}`} />
                      <span className="mabox-nav-item-label">{item.label}</span>
                    </div>
                  ))}
                </div>
              ))}
            </aside>

            <main className="mabox-main">
              <div className="mabox-breadcrumb">
                {breadcrumbs.map((crumb, idx) => (
                  <span key={idx}>
                    {idx > 0 && <span className="mabox-breadcrumb-sep">/</span>}
                    <span className={idx === breadcrumbs.length - 1 ? "mabox-breadcrumb-current" : ""}>
                      {crumb}
                    </span>
                  </span>
                ))}
              </div>
              <div className="mabox-content">
                {renderContent()}
              </div>
            </main>
          </div>

          <Affix offsetBottom={0}>
            <footer className="mabox-footer">
              <Save label="保存更改" />
            </footer>
          </Affix>
        </div>
      </DataContext.Provider>
    </>
  );
};

export default App;
