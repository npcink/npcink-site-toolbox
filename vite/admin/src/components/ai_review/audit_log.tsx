import React, { useState, useEffect } from "react";
import { aiReviewApi } from "@/api";
import { Table, Tag, Button, Space, Popconfirm, message, Typography } from "antd";
import type { ColumnsType } from "antd/es/table";


const { Text } = Typography;

interface LogEntry {
  comment_author: string;
  comment_email: string;
  comment_text: string;
  is_safe: boolean;
  confidence: number;
  reason: string;
  risk_level: string;
  provider: string;
  reviewed_at: string;
  status: string;
  reviewer_action: string;
}

const riskColor: Record<string, string> = {
  safe: "green",
  medium: "orange",
  high: "red",
};

const statusTag = (status: string) => {
  switch (status) {
    case "approved":
      return <Tag color="green">已通过</Tag>;
    case "rejected":
      return <Tag color="red">已拒绝</Tag>;
    default:
      return <Tag color="blue">待复核</Tag>;
  }
};

const App: React.FC = () => {
  const [logs, setLogs] = useState<LogEntry[]>([]);
  const [loading, setLoading] = useState(false);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);

  const fetchLogs = async (p = 1) => {
    setLoading(true);
    try {
      const res = await aiReviewApi.getLogs(p);
      setLogs(res.data?.items || []);
      setTotal(res.data?.total || 0);
      setPage(p);
    } catch {
      message.error("获取日志失败");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs(1);
  }, []);

  const handleReview = async (index: number, action: string) => {
    try {
      const result = await aiReviewApi.reviewItem(index, action);
      if (result.success) {
        message.success("操作成功");
        fetchLogs(page);
      } else {
        message.error(result.message || result.data?.error || "操作失败");
      }
    } catch {
      message.error("请求失败");
    }
  };

  const handleClear = async () => {
    try {
      const result = await aiReviewApi.clearLogs();
      if (result.success) {
        message.success("日志已清空");
        setLogs([]);
        setTotal(0);
      } else {
        message.error(result.message || result.data?.error || "清空失败");
      }
    } catch {
      message.error("请求失败");
    }
  };

  const columns: ColumnsType<LogEntry> = [
    {
      title: "评论者",
      dataIndex: "comment_author",
      width: 100,
      render: (text: string, record: LogEntry) => (
        <div>
          <div>{text || "匿名"}</div>
          <Text type="secondary" style={{ fontSize: 12 }}>
            {record.comment_email}
          </Text>
        </div>
      ),
    },
    {
      title: "评论内容",
      dataIndex: "comment_text",
      ellipsis: true,
      width: 250,
    },
    {
      title: "风险等级",
      dataIndex: "risk_level",
      width: 90,
      render: (level: string) => (
        <Tag color={riskColor[level] || "default"}>
          {level === "safe" ? "安全" : level === "medium" ? "中等" : "高危"}
        </Tag>
      ),
    },
    {
      title: "置信度",
      dataIndex: "confidence",
      width: 80,
      render: (v: number) => `${(v * 100).toFixed(0)}%`,
    },
    {
      title: "审核原因",
      dataIndex: "reason",
      ellipsis: true,
      width: 200,
    },
    {
      title: "引擎",
      dataIndex: "provider",
      width: 100,
    },
    {
      title: "状态",
      dataIndex: "status",
      width: 90,
      render: statusTag,
    },
    {
      title: "时间",
      dataIndex: "reviewed_at",
      width: 150,
    },
    {
      title: "操作",
      width: 160,
      render: (_: any, record: LogEntry, index: number) => {
        const globalIndex = (page - 1) * 20 + index;
        if (record.status !== "pending_review") return null;
        return (
          <Space>
            <Button
              type="link"
              size="small"
              onClick={() => handleReview(globalIndex, "approve")}
            >
              通过
            </Button>
            <Popconfirm
              title="确认拒绝此评论？"
              onConfirm={() => handleReview(globalIndex, "reject")}
            >
              <Button type="link" size="small" danger>
                拒绝
              </Button>
            </Popconfirm>
          </Space>
        );
      },
    },
  ];

  return (
    <div>
      <div style={{ marginBottom: 16, display: "flex", justifyContent: "space-between" }}>
        <h3 style={{ margin: 0 }}>审核日志</h3>
        <Popconfirm title="确认清空所有日志？" onConfirm={handleClear}>
          <Button danger size="small">
            清空日志
          </Button>
        </Popconfirm>
      </div>
      <Table
        columns={columns}
        dataSource={logs}
        loading={loading}
        rowKey={(_, i) => String(i)}
        pagination={{
          current: page,
          total,
          pageSize: 20,
          onChange: (p) => fetchLogs(p),
        }}
        scroll={{ x: 1200 }}
        size="small"
      />
    </div>
  );
};

export default App;
