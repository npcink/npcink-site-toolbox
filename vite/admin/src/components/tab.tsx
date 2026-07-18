import React, { lazy, Suspense, useCallback, useEffect, useState } from "react";

import FeatureSearch from "@/components/feature-search";
import {
  DataContext,
  emptySecretStatus,
  fetchSettings,
  SettingsLoadState,
} from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { Option, SecretChange, SecretChanges, SecretPath } from "@/tool/interface";
import { updateOptionValue } from "@/tool/option";
import {
  AdminView,
  adminViewSupportsTargetItem,
  getAdminViewFromSearch,
  isAdminView,
  normalizeAdminView,
  writeAdminViewToHistory,
} from "@/tool/navigation";
import Save from "@/tool/save";

const Dashboard = lazy(() => import("@/components/dashboard/index"));
const Page = lazy(() => import("@/components/page/index"));
const Optimize = lazy(() => import("@/components/optimize/index"));
const Function = lazy(() => import("@/components/function/index"));
const Domestic = lazy(() => import("@/components/domestic/index"));
const Performance = lazy(() => import("@/components/performance/index"));
const About = lazy(() => import("@/components/about/index"));

const TabFallback = (
  <div className="mabox-view-state mabox-view-state--loading" role="status" aria-live="polite">
    <span className="mabox-view-state-spinner" aria-hidden="true" />
    <span className="mabox-view-state-copy">
      <strong>正在加载当前页面</strong>
      <span>设置读取完成，正在准备页面内容。</span>
    </span>
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
  const [optionData, setOptionData] = useState<Option>(defaultVarOption);
  const [lastSavedOption, setLastSavedOption] = useState<Option>(defaultVarOption);
  const [secretStatus, setSecretStatus] = useState(emptySecretStatus);
  const [secretChanges, setSecretChanges] = useState<SecretChanges>({});
  const [settingsState, setSettingsState] = useState<SettingsLoadState>("loading");
  const [settingsError, setSettingsError] = useState<string | null>(null);
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

  const loadSettings = useCallback(async () => {
    setSettingsState("loading");
    setSettingsError(null);

    try {
      const response = await fetchSettings();
      setOptionData(response.data);
      setLastSavedOption(response.data);
      setSecretStatus(response.secretStatus);
      setSecretChanges({});
      setSettingsState("ready");
    } catch (error) {
      const message = error instanceof Error ? error.message : "无法读取设置";
      setSettingsError(message);
      setSettingsState("error");
      throw error;
    }
  }, []);

  useEffect(() => {
    loadSettings().catch(() => {});
  }, [loadSettings]);

  useEffect(() => {
    if (!mobileMenuOpen) return;

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") setMobileMenuOpen(false);
    };
    window.addEventListener("keydown", handleEscape);
    return () => window.removeEventListener("keydown", handleEscape);
  }, [mobileMenuOpen]);

  const updateOption = (father: string, son: string, newValue: any) => {
    setOptionData((previousOptionData) =>
      updateOptionValue(previousOptionData, father, son, newValue),
    );
  };

  const refreshOption = loadSettings;

  const setSecretChange = useCallback((path: SecretPath, change?: SecretChange) => {
    setSecretChanges((previous) => {
      const next = { ...previous };
      if (change) next[path] = change;
      else delete next[path];
      return next;
    });
  }, []);

  const clearSecretChanges = useCallback(() => setSecretChanges({}), []);

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

      const reduceMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)").matches ?? false;
      element.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "center" });
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
    if (targetItemId && adminViewSupportsTargetItem(item.key)) {
      extraProps.targetItemId = targetItemId;
    }

    if (settingsState === "loading") {
      return (
        <div className="mabox-view-state mabox-view-state--loading" role="status" aria-live="polite">
          <span className="mabox-view-state-spinner" aria-hidden="true" />
          <span className="mabox-view-state-copy">
            <strong>正在读取站点设置</strong>
            <span>读取完成前不会启用保存。</span>
          </span>
        </div>
      );
    }

    if (settingsState === "error") {
      return (
        <div className="mabox-view-state mabox-view-state--error" role="alert">
          <span className="dashicons dashicons-warning mabox-view-state-icon" aria-hidden="true" />
          <span className="mabox-view-state-copy">
            <strong>无法读取站点设置</strong>
            <span>{`${settingsError || "设置接口请求失败"}。为避免覆盖真实配置，保存功能已禁用。`}</span>
          </span>
          <button
            type="button"
            className="mabox-view-state-action"
            onClick={() => loadSettings().catch(() => {})}
          >
            重新读取
          </button>
        </div>
      );
    }

    return (
      <Suspense fallback={TabFallback}>
        <Component {...extraProps} />
      </Suspense>
    );
  };

  const breadcrumbs = [
    "Npcink Site Toolbox",
    activeGroup?.groupLabel || "工作台",
    activeNavItem?.label || "概览",
  ];

  return (
    <DataContext.Provider
      value={{
        optionData,
        updateOption,
        refreshOption,
        lastSavedOption,
        setLastSavedOption,
        secretStatus,
        secretChanges,
        setSecretChange,
        clearSecretChanges,
        settingsState,
        settingsError,
      }}
    >
      <div className="mabox-shell">
        <header className="mabox-header">
          <div className="mabox-header-left">
            <span className="dashicons dashicons-admin-generic mabox-header-icon" aria-hidden="true" />
            <div>
              <h1 className="mabox-header-title">Npcink Site Toolbox</h1>
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
            aria-label="Npcink Site Toolbox主导航"
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
            <Save />
          </footer>
        )}
      </div>
    </DataContext.Provider>
  );
};

export default App;
