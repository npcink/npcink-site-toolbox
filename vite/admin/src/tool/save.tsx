import { useContext, useState, useEffect } from "react";
import { Button, Space, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { saveOption } from "@/axios/save";
import { createSnapshot } from "@/tool/snapshot";
import { diffConfig, getDiffSummary } from "@/tool/diff";
import DiffModal from "@/components/diff-modal";
import { ConfigDiffItem } from "@/tool/interface";
import { UpOutlined } from "@ant-design/icons";

interface SaveProps {
  label?: string;
}

const App: React.FC<SaveProps> = ({ label = "保存" }) => {
  const { optionData, refreshOption, lastSavedOption } = useContext(DataContext);
  const [saving, setSaving] = useState(false);
  const [diffVisible, setDiffVisible] = useState(false);
  const [diffs, setDiffs] = useState<ConfigDiffItem[]>([]);

  const doSave = async () => {
    setSaving(true);
    try {
      createSnapshot(optionData);
      await saveOption(optionData);
      await refreshOption();
      message.success("保存成功");
    } catch (error) {
      message.error("保存失败，请重试");
    } finally {
      setSaving(false);
    }
  };

  const handleSaveClick = () => {
    const changes = diffConfig(lastSavedOption, optionData);
    const summary = getDiffSummary(changes);

    if (!summary.hasChanges) {
      message.info("配置未做任何更改，无需保存");
      return;
    }

    setDiffs(changes);
    setDiffVisible(true);
  };

  const handleConfirmSave = () => {
    setDiffVisible(false);
    doSave();
  };

  const [showButton, setShowButton] = useState(false);
  useEffect(() => {
    const handleScroll = () => {
      // 获取当前滚动的垂直距离
      const scrollY = window.scrollY || window.pageYOffset;
      // 设置一个阈值，例如 50vh，即页面高度的一半
      const threshold = window.innerHeight * 0.5;

      if (scrollY > threshold) {
        setShowButton(true);
      } else {
        setShowButton(false);
      }
    };

    window.addEventListener("scroll", handleScroll);
    return () => {
      window.removeEventListener("scroll", handleScroll);
    };
  }, []);

  const handleButtonClick = () => {
    // 滚动到页面顶部
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div>
      <Space size={"large"}>
        {showButton && (
          <Button
            type="text"
            shape="circle"
            onClick={handleButtonClick}
            icon={<UpOutlined />}
          ></Button>
        )}
        <Button type="primary" onClick={handleSaveClick} loading={saving}>
          {label}
        </Button>
      </Space>
      <DiffModal
        visible={diffVisible}
        diffs={diffs}
        onConfirm={handleConfirmSave}
        onCancel={() => setDiffVisible(false)}
      />
    </div>
  );
};

export default App;
