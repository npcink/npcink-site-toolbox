import { useState } from "react";
import Today from "./tab/today";
import Moon from "./tab/moon";

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
    </div>
  );
};

export default Tab;
