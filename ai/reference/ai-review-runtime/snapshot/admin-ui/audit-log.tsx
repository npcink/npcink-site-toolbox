import React, { useState, useEffect } from "react";
import { aiReviewApi } from "@/api";
import { Button, Space, Popconfirm, message } from "antd";
import { CheckTable, DetailDrawer, StatusTag } from "@/components/settings-ui";

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

const riskStatusMap: Record<string, "安全" | "谨慎" | "高风险"> = {
  safe: "安全",
  medium: "谨慎",
  high: "高风险",
};

const statusMap: Record<string, "已通过" | "已拒绝" | "待复核"> = {
  approved: "已通过",
  rejected: "已拒绝",
  pending_review: "待复核",
};

const App: React.FC = () => {
  const [logs, setLogs] = useState<LogEntry[]>([]);
  const [loading, setLoading] = useState(false);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [selectedLog, setSelectedLog] = useState<LogEntry | null>(null);

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

  const openDetail = (record: LogEntry) => {
    setSelectedLog(record);
    setDrawerOpen(true);
  };

  const columns = [
    {
      title: "评论者",
      dataIndex: "comment_author",
      key: "comment_author",
      width: 100,
      render: (text: string) => text || "匿名",
    },
    {
      title: "评论内容",
      dataIndex: "comment_text",
      key: "comment_text",
      width: 200,
      render: (text: string, record: LogEntry) => (
        <span style={{ cursor: "pointer" }} onClick={() => openDetail(record)}>
          {text.length > 40 ? text.slice(0, 40) + "..." : text}
        </span>
      ),
    },
    {
      title: "风险等级",
      dataIndex: "risk_level",
      key: "risk_level",
      width: 80,
      render: (level: string) => <StatusTag status={riskStatusMap[level] || "谨慎"} />,
    },
    {
      title: "置信度",
      dataIndex: "confidence",
      key: "confidence",
      width: 70,
      render: (v: number) => `${(v * 100).toFixed(0)}%`,
    },
    {
      title: "引擎",
      dataIndex: "provider",
      key: "provider",
      width: 80,
    },
    {
      title: "状态",
      dataIndex: "status",
      key: "status",
      width: 80,
      render: (status: string) => <StatusTag status={statusMap[status] || "待复核"} />,
    },
    {
      title: "时间",
      dataIndex: "reviewed_at",
      key: "reviewed_at",
      width: 140,
    },
    {
      title: "操作",
      key: "action",
      width: 180,
      render: (_: any, record: LogEntry, index: number) => {
        const globalIndex = (page - 1) * 20 + index;
        if (record.status !== "pending_review") {
          return (
            <Button type="link" size="small" onClick={() => openDetail(record)}>
              详情
            </Button>
          );
        }
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
            <Button type="link" size="small" onClick={() => openDetail(record)}>
              详情
            </Button>
          </Space>
        );
      },
    },
  ];

  const dataSource = logs.map((item, i) => ({
    ...item,
    key: String((page - 1) * 20 + i),
  }));

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
      <CheckTable
        columns={columns}
        dataSource={dataSource}
        loading={loading}
      />
      {total > 20 && (
        <div style={{ marginTop: 12, textAlign: "center" }}>
          <Space>
            <Button disabled={page <= 1} onClick={() => fetchLogs(page - 1)}>上一页</Button>
            <span style={{ fontSize: 12, color: "#666" }}>第 {page} 页 / 共 {Math.ceil(total / 20)} 页</span>
            <Button disabled={page >= Math.ceil(total / 20)} onClick={() => fetchLogs(page + 1)}>下一页</Button>
          </Space>
        </div>
      )}
      <DetailDrawer
        title="审核详情"
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        width={560}
      >
        {selectedLog && (
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            <div>
              <strong>评论者：</strong>
              {selectedLog.comment_author || "匿名"}
              {selectedLog.comment_email && (
                <span style={{ color: "#999", marginLeft: 8 }}>({selectedLog.comment_email})</span>
              )}
            </div>
            <div>
              <strong>评论全文：</strong>
              <div style={{ background: "#f5f5f5", padding: 8, borderRadius: 4, marginTop: 4 }}>
                {selectedLog.comment_text}
              </div>
            </div>
            <div>
              <strong>风险等级：</strong>
              <StatusTag status={riskStatusMap[selectedLog.risk_level] || "谨慎"} />
            </div>
            <div>
              <strong>置信度：</strong>
              {(selectedLog.confidence * 100).toFixed(0)}%
            </div>
            <div>
              <strong>审核原因：</strong>
              <div style={{ background: "#fffbe6", padding: 8, borderRadius: 4, marginTop: 4 }}>
                {selectedLog.reason || "无"}
              </div>
            </div>
            <div>
              <strong>审核引擎：</strong>
              {selectedLog.provider}
            </div>
            <div>
              <strong>审核状态：</strong>
              <StatusTag status={statusMap[selectedLog.status] || "待复核"} />
            </div>
            <div>
              <strong>审核时间：</strong>
              {selectedLog.reviewed_at}
            </div>
          </div>
        )}
      </DetailDrawer>
    </div>
  );
};

export default App;
