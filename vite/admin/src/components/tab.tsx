import React from "react";
import { useState, lazy, Suspense, useEffect, useRef, useCallback } from "react";
import { Tabs, Layout, Affix, Spin } from "antd";
import type { TabsProps } from "antd";

import { defaultOption, DataContext, fetchSettings } from "@/tool/dataContext";
import Save from "@/tool/save";
import FeatureSearch from "@/components/feature-search";

// 懒加载各 Tab 组件
const Dashboard = lazy(() => import("@/components/dashboard/index"));
const Page = lazy(() => import("@/components/page/index"));
const Optimize = lazy(() => import("@/components/optimize/index"));
const Login = lazy(() => import("@/components/login/index"));
const H5 = lazy(() => import("@/components/h5/index"));
const Function = lazy(() => import("@/components/function/index"));
const Shortcode = lazy(() => import("@/components/shortcode/index"));
const Template = lazy(() => import("@/components/template/index"));
const Domestic = lazy(() => import("@/components/domestic/index"));
const Performance = lazy(() => import("@/components/performance/index"));
const AiReview = lazy(() => import("@/components/ai_review/index"));
const Services = lazy(() => import("@/components/services/index"));
const Feedback = lazy(() => import("@/components/feedback/index"));
const About = lazy(() => import("@/components/about/index"));

const TabFallback = (
  <div style={{ display: "flex", justifyContent: "center", padding: "48px" }}>
    <Spin size="large" />
  </div>
);

const App: React.FC = () => {
  const [optionData, setOptionData] = useState(defaultOption);
  const [lastSavedOption, setLastSavedOption] = useState(defaultOption);
  const [isMobile, setIsMobile] = useState(window.innerWidth < 768);
  const [activeTab, setActiveTab] = useState("0");
  const tabsRef = useRef<any>(null);

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

  const items: TabsProps["items"] = [
    {
      key: "0",
      label: `仪表盘`,
      children: <Suspense fallback={TabFallback}><Dashboard onNavigate={handleSearchNavigate} /></Suspense>,
    },
    {
      key: "1",
      label: `页面`,
      children: <Suspense fallback={TabFallback}><Page /></Suspense>,
    },
    {
      key: "2",
      label: `优化`,
      children: <Suspense fallback={TabFallback}><Optimize /></Suspense>,
    },
    {
      key: "3",
      label: `登录页`,
      children: <Suspense fallback={TabFallback}><Login /></Suspense>,
    },
    {
      key: "4",
      label: `H5`,
      children: <Suspense fallback={TabFallback}><H5 /></Suspense>,
    },
    {
      key: "5",
      label: `功能`,
      children: <Suspense fallback={TabFallback}><Function /></Suspense>,
    },
    {
      key: "7",
      label: `短代码`,
      children: <Suspense fallback={TabFallback}><Shortcode /></Suspense>,
    },
    {
      key: "8",
      label: `页面模版`,
      children: <Suspense fallback={TabFallback}><Template /></Suspense>,
    },
    {
      key: "10",
      label: `国内生态`,
      children: <Suspense fallback={TabFallback}><Domestic /></Suspense>,
    },
    {
      key: "11",
      label: `性能优化`,
      children: <Suspense fallback={TabFallback}><Performance /></Suspense>,
    },
    {
      key: "12",
      label: `AI 审核`,
      children: <Suspense fallback={TabFallback}><AiReview /></Suspense>,
    },
    {
      key: "13",
      label: `技术支持`,
      children: <Suspense fallback={TabFallback}><Services /></Suspense>,
    },
    {
      key: "14",
      label: `用户反馈`,
      children: <Suspense fallback={TabFallback}><Feedback /></Suspense>,
    },
    {
      key: "9",
      label: `关于`,
      children: <Suspense fallback={TabFallback}><About /></Suspense>,
    },
  ];

  const { Header, Footer, Content } = Layout;

  const headerStyle: React.CSSProperties = {
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    height: 64,
    paddingInline: 48,
    lineHeight: "64px",
    borderBottom: "1px solid #ccd0d4",
    background: "linear-gradient(#fefefe, #f5f5f5)",
  };

  const footerStyle: React.CSSProperties = {
    float: "right",
    /*borderBottom: "1px solid #ccd0d4",*/
    background: "linear-gradient(#fefefe, #f5f5f5)",
  };
  return (
    <>
      <DataContext.Provider value={{ optionData, updateOption, refreshOption, lastSavedOption, setLastSavedOption }}>
        <div className="MaBox_option">
          <Layout>
            <Affix offsetTop={30}>
              <Header style={headerStyle}>
                <HeaderBlock onNavigate={handleSearchNavigate} />
              </Header>
            </Affix>
            <Content className="mabox_content">
              <Tabs
                activeKey={activeTab}
                onChange={setActiveTab}
                defaultActiveKey="0"
                tabPosition={isMobile ? "top" : "left"}
                items={items}
                ref={tabsRef}
              />
            </Content>
            <Footer style={footerStyle}>
              <div className="float-right">
                <Save />
              </div>
            </Footer>
          </Layout>
        </div>
      </DataContext.Provider>
    </>
  );
};

const HeaderBlock: React.FC<{ onNavigate: (tabKey: string, itemId: string) => void }> = ({ onNavigate }) => {
  return (
    <>
      <h1 className="text-2xl leading-7 font-medium">
        魔法工具箱
        <small className="text-xs font-light text-gray-400 ml-2 ">
          <a target="_blank" href="https://www.npc.ink">
            For Npcink
          </a>
        </small>
      </h1>
      <FeatureSearch onNavigate={onNavigate} />
      <Save />
    </>
  );
};

export default App;
