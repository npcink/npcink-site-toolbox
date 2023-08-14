//Tab切换
import { useState } from "react";
import Today from "@/components/page/b2Shop/tab/today";
import Month from "@/components/page/b2Shop/tab/month";

const Tab = () => {
  const [activeTab, setActiveTab] = useState(0);

  const tabs = [
    {
      title: "本周",
      content: <Today />,
    },
    {
      title: "本月",
      content: <Month />,
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
        {tabs.map(
          (tab, index) =>
            activeTab === index && <div key={index}>{tab.content}</div>
        )}
      </div>
      <div className="describe">总销售（已减退款），总订单（已减退款）。</div>
      <hr />
    </div>
  );
};

export default Tab;
