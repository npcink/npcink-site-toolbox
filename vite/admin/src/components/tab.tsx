import React, { lazy, Suspense, useCallback, useEffect, useState } from "react";

import FeatureSearch from "@/components/feature-search";
import { defaultOption, DataContext, fetchSettings } from "@/tool/dataContext";
import {
  AdminView,
  getAdminViewFromSearch,
  isAdminView,
  normalizeAdminView,
  writeAdminViewToHistory,
} from "@/tool/navigation";
import Save from "@/tool/save";

const Dashboard = lazy(() => import("@/components/dashboard/index"));
const Page = lazy(() => import("@/components/page/index"));
const Optimize = lazy(() => import("@/components/optimize/index"));
const Login = lazy(() => import("@/components/login/index"));
const Function = lazy(() => import("@/components/function/index"));
const Domestic = lazy(() => import("@/components/domestic/index"));
const Performance = lazy(() => import("@/components/performance/index"));
const About = lazy(() => import("@/components/about/index"));

const TabFallback = (
  <div className="mabox-view-loading" role="status" aria-live="polite">
    <span className="mabox-view-loading-spinner" aria-hidden="true" />
    <span>正在加载设置…</span>
  </div>
);

interface NavItem {
  key: AdminView;
  label: string;
  icon: string;
  component: React.LazyExoticComponent<React.FC<any>>;
}

interface NavGroup {
  groupLabel: string;
  items: NavItem[];
}

const navGroups: NavGroup[] = [
  {
    groupLabel: "工作台",
    items: [
      { key: "overview", label: "概览", icon: "dashicons-dashboard", component: Dashboard },
    ],
  },
  {
    groupLabel: "站点设置",
    items: [
      { key: "site", label: "站点与媒体", icon: "dashicons-admin-site-alt3", component: Optimize },
      { key: "content", label: "内容与页面", icon: "dashicons-admin-page", component: Page },
      { key: "seo", label: "SEO 与增强", icon: "dashicons-search", component: Function },
      { key: "security", label: "登录与安全", icon: "dashicons-lock", component: Login },
      { key: "china", label: "国内生态", icon: "dashicons-location-alt", component: Domestic },
    ],
  },
  {
    groupLabel: "工具与支持",
    items: [
      { key: "maintenance", label: "维护工具", icon: "dashicons-admin-tools", component: Performance },
      { key: "about", label: "关于与帮助", icon: "dashicons-info-outline", component: About },
    ],
  },
];

const allNavItems = navGroups.flatMap((group) => group.items);

const App: React.FC = () => {
  const [optionData, setOptionData] = useState(defaultOption);
  const [lastSavedOption, setLastSavedOption] = useState(defaultOption);
  const [isMobile, setIsMobile] = useState(() => window.innerWidth <= 782);
  const [activeView, setActiveView] = useState<AdminView>(() =>
    getAdminViewFromSearch(window.location.search),
  );
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [targetItemId, setTargetItemId] = useState<string | null>(null);

  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth <= 782);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  useEffect(() => {
    const requestedView = new URLSearchParams(window.location.search).get("view");
    if (!isAdminView(requestedView)) {
      writeAdminViewToHistory("overview", "replace");
    }

    const handlePopState = () => {
      const requestedView = new URLSearchParams(window.location.search).get("view");
      const nextView = normalizeAdminView(requestedView);
      setActiveView(nextView);
      if (!isAdminView(requestedView)) {
        writeAdminViewToHistory(nextView, "replace");
      }
      setTargetItemId(null);
      setMobileMenuOpen(false);
    };
    window.addEventListener("popstate", handlePopState);
    return () => window.removeEventListener("popstate", handlePopState);
  }, []);

  useEffect(() => {
    fetchSettings().then((data) => {
      setOptionData(data);
      setLastSavedOption(data);
    });
  }, []);

  useEffect(() => {
    if (!mobileMenuOpen) return;

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") setMobileMenuOpen(false);
    };
    window.addEventListener("keydown", handleEscape);
    return () => window.removeEventListener("keydown", handleEscape);
  }, [mobileMenuOpen]);

  const updateOption = (father: string, son: string, newValue: any) => {
    setOptionData((previousOptionData) => {
      const updatedOptionData = { ...previousOptionData };
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

  const navigateToView = useCallback((requestedView: string, itemId?: string) => {
    const nextView = normalizeAdminView(requestedView);
    const currentView = getAdminViewFromSearch(window.location.search);

    setActiveView(nextView);
    setTargetItemId(itemId || null);
    setMobileMenuOpen(false);

    if (nextView !== currentView || !isAdminView(new URLSearchParams(window.location.search).get("view"))) {
      writeAdminViewToHistory(nextView);
    }
    window.requestAnimationFrame(() => {
      document.getElementById("mabox-main-content")?.focus({ preventScroll: true });
    });
  }, []);

  const handleSearchNavigate = useCallback((view: string, itemId?: string) => {
    navigateToView(view, itemId);
  }, [navigateToView]);

  useEffect(() => {
    if (!targetItemId) return;

    let attempts = 0;
    let retryTimer: number | undefined;
    let highlightTimer: number | undefined;

    const locateTarget = () => {
      const itemId = targetItemId;
      let element = document.getElementById(itemId);
      if (!element) {
        element = document.querySelector(`[data-search-aliases~="${itemId}"]`);
      }
      if (!element && attempts < 20) {
        attempts += 1;
        retryTimer = window.setTimeout(locateTarget, 100);
        return;
      }
      if (!element) return;

      element.scrollIntoView({ behavior: "smooth", block: "center" });
      element.classList.add("mabox-search-target");
      highlightTimer = window.setTimeout(() => element?.classList.remove("mabox-search-target"), 2000);
    };

    locateTarget();
    return () => {
      if (retryTimer) window.clearTimeout(retryTimer);
      if (highlightTimer) window.clearTimeout(highlightTimer);
    };
  }, [activeView, targetItemId]);

  const activeNavItem = allNavItems.find((item) => item.key === activeView);
  const activeGroup = navGroups.find((group) =>
    group.items.some((item) => item.key === activeView),
  );

  const renderContent = () => {
    const item = allNavItems.find((navItem) => navItem.key === activeView);
    if (!item) return null;

    const Component = item.component;
    const extraProps: Record<string, unknown> = {};
    if (item.key === "overview") {
      extraProps.onNavigate = handleSearchNavigate;
    }
    if (targetItemId && ["content", "seo", "china"].includes(item.key)) {
      extraProps.targetItemId = targetItemId;
    }

    return (
      <Suspense fallback={TabFallback}>
        <Component {...extraProps} />
      </Suspense>
    );
  };

  const breadcrumbs = [
    "魔法工具箱",
    activeGroup?.groupLabel || "工作台",
    activeNavItem?.label || "概览",
  ];

  return (
    <DataContext.Provider
      value={{ optionData, updateOption, refreshOption, lastSavedOption, setLastSavedOption }}
    >
      <div className="mabox-shell">
        <header className="mabox-header">
          <div className="mabox-header-left">
            <span className="dashicons dashicons-admin-generic mabox-header-icon" aria-hidden="true" />
            <div>
              <h1 className="mabox-header-title">魔法工具箱</h1>
              <p className="mabox-header-subtitle">站点设置与维护</p>
            </div>
          </div>

          {!isMobile && (
            <div className="mabox-header-center">
              <FeatureSearch onNavigate={handleSearchNavigate} style={{ maxWidth: 320 }} />
            </div>
          )}

          <div className="mabox-header-right">
            <button
              type="button"
              className="mabox-help-btn"
              aria-label="打开帮助"
              onClick={() => navigateToView("about")}
            >
              <span className="dashicons dashicons-editor-help" aria-hidden="true" />
              <span>帮助</span>
            </button>
            {!isMobile && <Save />}
          </div>
        </header>

        <div className="mabox-body">
          {isMobile && (
            <div className="mabox-mobile-search">
              <FeatureSearch onNavigate={handleSearchNavigate} style={{ maxWidth: "100%" }} />
            </div>
          )}

          {isMobile && (
            <button
              type="button"
              className="mabox-mobile-toggle"
              aria-controls="mabox-primary-navigation"
              aria-expanded={mobileMenuOpen}
              onClick={() => setMobileMenuOpen((isOpen) => !isOpen)}
            >
              <span className="dashicons dashicons-menu" aria-hidden="true" />
              <span>{activeNavItem?.label || "导航"}</span>
            </button>
          )}

          {isMobile && mobileMenuOpen && (
            <button
              type="button"
              className="mabox-mobile-nav-overlay"
              aria-label="关闭导航"
              onClick={() => setMobileMenuOpen(false)}
            />
          )}

          <nav
            id="mabox-primary-navigation"
            className={`mabox-sidebar ${isMobile && mobileMenuOpen ? "mabox-sidebar--open" : ""}`}
            aria-label="魔法工具箱主导航"
          >
            {navGroups.map((group) => (
              <div className="mabox-nav-group" key={group.groupLabel}>
                <div className="mabox-nav-group-label">{group.groupLabel}</div>
                {group.items.map((item) => (
                  <button
                    type="button"
                    key={item.key}
                    className={`mabox-nav-item ${activeView === item.key ? "mabox-nav-item--active" : ""}`}
                    aria-current={activeView === item.key ? "page" : undefined}
                    onClick={() => navigateToView(item.key)}
                  >
                    <span className={`dashicons ${item.icon}`} aria-hidden="true" />
                    <span className="mabox-nav-item-label">{item.label}</span>
                  </button>
                ))}
              </div>
            ))}
          </nav>

          <main className="mabox-main" id="mabox-main-content" tabIndex={-1}>
            <nav className="mabox-breadcrumb" aria-label="面包屑">
              <ol>
                {breadcrumbs.map((crumb, index) => (
                  <li
                    key={crumb}
                    className={index === breadcrumbs.length - 1 ? "mabox-breadcrumb-current" : ""}
                  >
                    {crumb}
                  </li>
                ))}
              </ol>
            </nav>
            <div className="mabox-content">{renderContent()}</div>
          </main>
        </div>

        {isMobile && (
          <footer className="mabox-footer">
            <Save label="保存更改" />
          </footer>
        )}
      </div>
    </DataContext.Provider>
  );
};

export default App;
