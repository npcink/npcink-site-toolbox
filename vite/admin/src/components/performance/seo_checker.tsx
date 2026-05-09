import React, { useContext, useState, useEffect } from "react";
import { Form, Button, List, Alert, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.seo_checker || {};
  const [formData, setFormData] = useState(publicData || {});
  const [issues, setIssues] = useState<any[]>([]);
  const [checking, setChecking] = useState(false);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "seo_checker", formData);
  }, [formData]);

  const handleCheck = () => {
    setChecking(true);
    const formData2 = new FormData();
    formData2.append("action", "mabox_seo_check");
    fetch(window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php", {
      method: "POST",
      body: formData2,
    })
      .then((r) => r.json())
      .then((res) => {
        setChecking(false);
        if (res.success) {
          setIssues(res.data.issues || []);
        }
      })
      .catch(() => {
        setChecking(false);
        message.error("检查失败");
      });
  };

  const handleFixAlt = () => {
    const formData2 = new FormData();
    formData2.append("action", "mabox_seo_fix_alt");
    fetch(window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php", {
      method: "POST",
      body: formData2,
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.success) {
          message.success("已补全 " + res.data.fixed + " 张图片的 Alt");
        }
      })
      .catch(() => message.error("修复失败"));
  };

  return (
    <Form
      name="seo_checker"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"SEO 健康度检查"}>
        <h2>SEO 检查助手</h2>
      </Form.Item>

      <Form.Item label="启用" name="enabled" valuePropName="checked">
        <FeatureSwitch featureId="performance-seo_checker-enabled" />
      </Form.Item>

      <Form.Item wrapperCol={{ offset: fromConfig.labelCol, span: fromConfig.wrapperCol }}>
        <Button type="primary" onClick={handleCheck} loading={checking}>
          开始检查
        </Button>
        <Button style={{ marginLeft: 8 }} onClick={handleFixAlt}>
          一键补全 Alt
        </Button>
      </Form.Item>

      {issues.length > 0 && (
        <Form.Item wrapperCol={{ offset: fromConfig.labelCol, span: fromConfig.wrapperCol }}>
          <Alert message={"发现 " + issues.length + " 个问题"} type="warning" />
          <List
            size="small"
            dataSource={issues}
            renderItem={(item: any) => (
              <List.Item>
                <strong>{item.type}：</strong>{item.message}
              </List.Item>
            )}
          />
        </Form.Item>
      )}
    </Form>
  );
};

export default App;