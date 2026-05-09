import React, { useContext, useState, useEffect } from "react";
import { Form, Button, Select, List, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.db_clean || {};
  const [formData, setFormData] = useState(publicData || {});
  const [stats, setStats] = useState<any>({});
  const [loading, setLoading] = useState(false);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "db_clean", formData);
  }, [formData]);

  const fetchStats = () => {
    const formData2 = new FormData();
    formData2.append("action", "mabox_db_stats");
    fetch(window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php", {
      method: "POST",
      body: formData2,
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.success) setStats(res.data);
      });
  };

  const handleClean = (type: string) => {
    setLoading(true);
    const formData2 = new FormData();
    formData2.append("action", "mabox_db_clean");
    formData2.append("type", type);
    fetch(window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php", {
      method: "POST",
      body: formData2,
    })
      .then((r) => r.json())
      .then((res) => {
        setLoading(false);
        if (res.success) {
          message.success("清理完成" + (res.data.deleted ? "，删除 " + res.data.deleted + " 条" : ""));
          fetchStats();
        }
      })
      .catch(() => {
        setLoading(false);
        message.error("清理失败");
      });
  };

  return (
    <Form
      name="db_clean"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"数据库清理与优化"}>
        <h2>数据库清理优化</h2>
      </Form.Item>

      <Form.Item label="启用" name="enabled" valuePropName="checked">
        <FeatureSwitch featureId="performance-db_clean-enabled" />
      </Form.Item>

      <Form.Item label="清理修订版本" name="clean_revisions" valuePropName="checked">
        <FeatureSwitch featureId="performance-db_clean-clean_revisions" />
      </Form.Item>
      <Form.Item label="清理自动草稿" name="clean_drafts" valuePropName="checked">
        <FeatureSwitch featureId="performance-db_clean-clean_drafts" />
      </Form.Item>
      <Form.Item label="清理垃圾评论" name="clean_spam_comments" valuePropName="checked">
        <FeatureSwitch featureId="performance-db_clean-clean_spam_comments" />
      </Form.Item>
      <Form.Item label="清理过期 Transient" name="clean_transients" valuePropName="checked">
        <FeatureSwitch featureId="performance-db_clean-clean_transients" />
      </Form.Item>

      <Form.Item label="定时自动清理" name="auto_clean" valuePropName="checked">
        <FeatureSwitch featureId="performance-db_clean-auto_clean" />
      </Form.Item>
      {formData.auto_clean && (
        <Form.Item label="清理周期" name="auto_clean_schedule">
          <Select options={[
            { label: "每天", value: "daily" },
            { label: "每周", value: "weekly" },
            { label: "每月", value: "monthly" },
          ]} />
        </Form.Item>
      )}

      <Form.Item wrapperCol={{ offset: fromConfig.labelCol, span: fromConfig.wrapperCol }}>
        <Button onClick={fetchStats}>查看统计</Button>
        <Button type="primary" danger style={{ marginLeft: 8 }} onClick={() => handleClean("all")} loading={loading}>
          一键清理全部
        </Button>
      </Form.Item>

      {stats.db_size && (
        <Form.Item wrapperCol={{ offset: fromConfig.labelCol, span: fromConfig.wrapperCol }}>
          <List size="small" bordered>
            <List.Item>数据库大小：{stats.db_size}</List.Item>
            <List.Item>修订版本：{stats.revisions} <Button size="small" onClick={() => handleClean("revisions")}>清理</Button></List.Item>
            <List.Item>自动草稿：{stats.drafts} <Button size="small" onClick={() => handleClean("drafts")}>清理</Button></List.Item>
            <List.Item>垃圾评论：{stats.spam} <Button size="small" onClick={() => handleClean("spam")}>清理</Button></List.Item>
            <List.Item>Transient：{stats.transients} <Button size="small" onClick={() => handleClean("transients")}>清理</Button></List.Item>
          </List>
        </Form.Item>
      )}
    </Form>
  );
};

export default App;