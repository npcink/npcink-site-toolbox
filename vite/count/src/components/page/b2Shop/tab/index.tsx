//Tab切换
import { useState } from "react";
import Today from "./today";
import Moon from "./moon";

const Tab = () => {
  const [activeTab, setActiveTab] = useState(0);

  const tabs = [
    {
      title: "本周",
      content: <Today />,
    },
    {
      title: "本月",
      content: <Moon />,
    },
    {
      title: "Tab 3",
      content: "Content of Tab 3",
    },
  ];

  const handleTabClick = (index: number) => {
    setActiveTab(index);
  };

  return (
    <div className="tab">
      <div className="tab-header">
        {tabs.map((tab, index) => (
          <button
          type="button"
            key={index}
            className={`tab-button ${activeTab === index ? "active" : ""}`}
            onClick={() => handleTabClick(index)}
          >
            {tab.title}
          </button>
        ))}
      </div>
      <div className="tab-content">
        {tabs.map((tab, index) => (
          <div
            key={index}
            className={`tab-item ${activeTab === index ? "active" : ""}`}
          >
            {activeTab === index && tab.content}
          </div>
        ))}
      </div>
      <div className="describe">总销售（已减退款），总订单（已减退款）。</div>
      <hr />
    </div>
  );
};

export default Tab;
