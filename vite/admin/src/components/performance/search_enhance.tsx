import React, { useContext, useState, useEffect } from "react";
import { Form, Card, Row, Col, Statistic, Tag, Divider, Alert, Empty, Spin } from "antd";
import { SearchOutlined, ExclamationCircleOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { searchHealthApi } from "@/api";
import { SearchHealthSummary } from "@/tool/interface";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;

const SearchHealthPanel: React.FC = () => {
  const [data, setData] = useState<SearchHealthSummary | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  useEffect(() => {
    setLoading(true);
    searchHealthApi
      .getSummary(30)
      .then((res: any) => {
        if (res?.success && res?.data) {
          setData(res.data as SearchHealthSummary);
          setError(false);
        }
      })
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <Spin style={{ display: "block", margin: "24px auto" }} />;
  if (error) return <Alert type="warning" message="搜索健康数据加载失败" showIcon />;
  if (!data || data.total_searches === 0) {
    return (
      <Empty
        image={Empty.PRESENTED_IMAGE_SIMPLE}
        description="暂无搜索数据，开启热词统计后积累数据"
      />
    );
  }

  return (
    <Card size="small" title={<span><SearchOutlined style={{ marginRight: 6 }} />搜索健康分析</span>}>
      <Row gutter={[16, 12]}>
        <Col span={8}>
          <Statistic title="总搜索量" value={data.total_searches} />
        </Col>
        <Col span={8}>
          <Statistic title="唯一关键词" value={data.unique_terms} />
        </Col>
        <Col span={8}>
          <Statistic
            title="无结果比例"
            value={data.total_searches > 0 ? Math.round((data.no_result_terms.reduce((s, t) => s + t.no_result_count, 0) / data.total_searches) * 100) : 0}
            suffix="%"
            valueStyle={{ color: data.no_result_terms.length > 0 ? "#faad14" : "#52c41a" }}
          />
        </Col>
      </Row>
      {data.top_terms.length > 0 && (
        <>
          <Divider orientation="left" style={{ margin: "12px 0 8px" }}>热门搜索词</Divider>
          <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
            {data.top_terms.slice(0, 15).map((t) => (
              <Tag key={t.term} color="blue">{t.term} ({t.count})</Tag>
            ))}
          </div>
        </>
      )}
      {data.no_result_terms.length > 0 && (
        <>
          <Divider orientation="left" style={{ margin: "12px 0 8px" }}>无结果搜索词</Divider>
          <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
            {data.no_result_terms.slice(0, 15).map((t) => (
              <Tag key={t.term} color="orange">{t.term} ({t.no_result_count})</Tag>
            ))}
          </div>
        </>
      )}
      {data.suspicious_terms && data.suspicious_terms.length > 0 && (
        <>
          <Divider orientation="left" style={{ margin: "12px 0 8px" }}>异常高频词</Divider>
          {data.suspicious_terms.map((s) => (
            <div key={s.term} style={{ marginBottom: 4 }}>
              <Tag color="red">{s.term}</Tag>
              <span style={{ color: "#999", fontSize: 12 }}>{s.reason}</span>
            </div>
          ))}
        </>
      )}
      {data.recommendations && data.recommendations.length > 0 && (
        <>
          <Divider orientation="left" style={{ margin: "12px 0 8px" }}>建议</Divider>
          {data.recommendations.map((r) => (
            <div key={r.id} style={{ marginBottom: 4 }}>
              <ExclamationCircleOutlined style={{ color: "#faad14", marginRight: 4 }} />
              <strong>{r.title}</strong>：{r.reason}
            </div>
          ))}
        </>
      )}
    </Card>
  );
};

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.search_enhance || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "search_enhance", formData);
  }, [formData]);

  return (
    <Form
      name="search_enhance"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"站内搜索体验增强"}>
        <h2>站内搜索增强</h2>
      </Form.Item>

      <Form.Item label="关键词高亮" name="highlight_enabled" valuePropName="checked">
        <FeatureSwitch featureId="performance-search_enhance-highlight_enabled" />
      </Form.Item>

      <Form.Item label="无结果推荐" name="recommend_enabled" valuePropName="checked"
        extra="搜索无结果时显示热门标签">
        <FeatureSwitch featureId="performance-search_enhance-recommend_enabled" />
      </Form.Item>

      <Form.Item label="热词统计" name="hotwords_enabled" valuePropName="checked"
        extra="记录搜索热词（后台可查看）">
        <FeatureSwitch featureId="performance-search_enhance-hotwords_enabled" />
      </Form.Item>

      <Form.Item wrapperCol={{ offset: fromConfig.labelCol.span, span: fromConfig.wrapperCol.span }}>
        <SearchHealthPanel />
      </Form.Item>
    </Form>
  );
};

export default App;
