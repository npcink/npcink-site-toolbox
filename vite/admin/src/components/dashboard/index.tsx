import React, { useContext, useState, useMemo, useCallback, useEffect } from "react";
import {
  Card,
  Statistic,
  Row,
  Col,
  Button,
  Space,
  Typography,
  message,
  Progress,
  List,
  Tag,
  Modal,
  Divider,
  Badge,
  Input,
  Alert,
} from "antd";
import {
  SafetyOutlined,
  ThunderboltOutlined,
  ExclamationCircleOutlined,
  StarOutlined,
  ArrowRightOutlined,
  CustomerServiceOutlined,
  FileTextOutlined,
  RocketOutlined,
  SearchOutlined,
} from "@ant-design/icons";
import { DataContext, serverDefaults } from "@/tool/dataContext";
import { saveOption } from "@/axios/save";
import { diagnosticsApi, searchHealthApi, settingsApi } from "@/api";
import { DiagnosticSummary, DiagnosticItem, SearchHealthSummary } from "@/tool/interface";
import { getAllPresets, Preset, saveCustomPreset, deleteCustomPreset } from "@/tool/presets";
import { getSnapshots, deleteSnapshot, restoreSnapshot, clearSnapshots, Snapshot, getDefaultConfig } from "@/tool/snapshot";
import { Dropdown } from "antd";
import FavoritesPanel from "@/components/favorites-panel";
import WizardModal from "@/components/wizard";

const { Title, Text, Paragraph } = Typography;
const { confirm } = Modal;

interface FeatureStats {
  total: number;
  enabled: number;
  disabled: number;
}

function countFeatures(data: any): FeatureStats {
  let total = 0;
  let enabled = 0;
  let disabled = 0;

  function traverse(obj: any) {
    if (obj === null || typeof obj !== "object" || Array.isArray(obj)) return;

    Object.values(obj).forEach((value) => {
      if (typeof value === "boolean") {
        total++;
        if (value) enabled++;
        else disabled++;
      } else if (typeof value === "string") {
        total++;
        if (value !== "false" && value !== "") enabled++;
        else disabled++;
      } else if (
        typeof value === "object" &&
        value !== null &&
        !Array.isArray(value)
      ) {
        traverse(value);
      }
    });
  }

  traverse(data);
  return { total, enabled, disabled };
}

interface Recommendation {
  id: string;
  label: string;
  tabKey: string;
  section: string;
  reason: string;
  type: "recommend" | "caution";
}

function getRecommendations(optionData: any): Recommendation[] {
  const recs: Recommendation[] = [];
  const cautions: Recommendation[] = [];

  if (!optionData.optimize?.site?.remove_RSS_version) {
    recs.push({
      id: "optimize-site-remove_RSS_version",
      label: "移除 WP 版本号",
      tabKey: "2",
      section: "站点",
      reason: "隐藏 WordPress 版本信息，提升安全性",
      type: "recommend",
    });
  }

  if (!optionData.page?.function?.search_limit) {
    recs.push({
      id: "page-function-search_limit",
      label: "限制搜索频次",
      tabKey: "1",
      section: "功能",
      reason: "防止恶意搜索攻击，减轻服务器压力",
      type: "recommend",
    });
  }

  if (!optionData.optimize?.medium?.img_add_tag) {
    recs.push({
      id: "optimize-medium-img_add_tag",
      label: "图片 Alt 自动补全",
      tabKey: "2",
      section: "媒体",
      reason: "提升图片 SEO，增加搜索引擎收录概率",
      type: "recommend",
    });
  }

  if (!optionData.function?.seo?.seo_home) {
    recs.push({
      id: "function-seo-seo_home",
      label: "首页 TDK",
      tabKey: "5",
      section: "SEO",
      reason: "优化首页搜索引擎展示效果",
      type: "recommend",
    });
  }

  if (!optionData.login?.security?.login_code || optionData.login?.security?.login_code === "false") {
    recs.push({
      id: "login-security-login_code",
      label: "登录验证码",
      tabKey: "3",
      section: "安全",
      reason: "防止暴力破解登录密码",
      type: "recommend",
    });
  }

  if (!optionData.optimize?.site?.hide_top_toolbar) {
    recs.push({
      id: "optimize-site-hide_top_toolbar",
      label: "隐藏顶部工具条",
      tabKey: "2",
      section: "站点",
      reason: "前台页面更清爽，提升用户体验",
      type: "recommend",
    });
  }

  if (optionData.page?.feature?.background_effect && optionData.page?.feature?.background_effect !== "false") {
    cautions.push({
      id: "page-feature-background_effect",
      label: "背景特效",
      tabKey: "1",
      section: "外观",
      reason: "可能影响页面性能，移动端用户体验下降",
      type: "caution",
    });
  }

  if (optionData.page?.jurisdiction?.ban_copy) {
    cautions.push({
      id: "page-jurisdiction-ban_copy",
      label: "禁止复制",
      tabKey: "1",
      section: "权限",
      reason: "可能影响正常用户复制代码或引用内容",
      type: "caution",
    });
  }

  if (optionData.page?.function?.top_ad) {
    cautions.push({
      id: "page-function-top_ad",
      label: "顶部广告 Script",
      tabKey: "1",
      section: "功能",
      reason: "注意广告代码安全，避免引入恶意脚本",
      type: "caution",
    });
  }

  return [...recs, ...cautions];
}

function getSecurityStatus(optionData: any) {
  const items = [];

  const hasLoginProtection = optionData.login?.security?.login_code && optionData.login?.security?.login_code !== "false";
  items.push({
    label: "登录保护",
    status: hasLoginProtection ? "active" : "inactive",
    detail: hasLoginProtection ? "已启用验证码" : "未启用",
  });

  const hasCommentProtection = optionData.page?.comment?.interval || optionData.page?.comment?.sensitive_words;
  items.push({
    label: "评论防护",
    status: hasCommentProtection ? "partial" : "inactive",
    detail: hasCommentProtection ? "部分启用" : "未启用",
  });

  const hasSeo = optionData.function?.seo?.seo_home || optionData.function?.seo?.seo_single;
  items.push({
    label: "SEO 优化",
    status: hasSeo ? "active" : "inactive",
    detail: hasSeo ? "已启用" : "未启用",
  });

  return items;
}


function deepMerge(target: any, source: any): any {
  if (source === null || typeof source !== "object") return source;
  if (target === null || typeof target !== "object") return source;

  const result = { ...target };

  Object.keys(source).forEach((key) => {
    if (
      source[key] &&
      typeof source[key] === "object" &&
      !Array.isArray(source[key])
    ) {
      result[key] = deepMerge(result[key] || {}, source[key]);
    } else {
      result[key] = source[key];
    }
  });

  return result;
}


interface DashboardProps {
  onNavigate?: (tabKey: string, itemId: string) => void;
}

const Dashboard: React.FC<DashboardProps> = ({ onNavigate }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const [applying, setApplying] = useState<string | null>(null);
  const [saveModalVisible, setSaveModalVisible] = useState(false);
  const [presetName, setPresetName] = useState("");
  const [presetDesc, setPresetDesc] = useState("");
  const [allPresets, setAllPresets] = useState<Preset[]>(getAllPresets());
  const [backupVisible, setBackupVisible] = useState(false);
  const [snapshots, setSnapshots] = useState<Snapshot[]>(getSnapshots());
  const [diagnosticSummary, setDiagnosticSummary] = useState<DiagnosticSummary | null>(null);
  const [diagnosticLoading, setDiagnosticLoading] = useState(false);
  const [searchHealth, setSearchHealth] = useState<SearchHealthSummary | null>(null);
  const [searchHealthLoading, setSearchHealthLoading] = useState(false);
  const [searchHealthError, setSearchHealthError] = useState(false);
  const [wizardVisible, setWizardVisible] = useState(false);
  const [wizardCompleted, setWizardCompleted] = useState(false);

  useEffect(() => {
    setDiagnosticLoading(true);
    diagnosticsApi
      .getSummary()
      .then((res: any) => {
        if (res?.success && res?.data) {
          setDiagnosticSummary(res.data as DiagnosticSummary);
        }
      })
      .catch((err: any) => {
        console.error("获取诊断摘要失败:", err);
      })
      .finally(() => {
        setDiagnosticLoading(false);
      });

    settingsApi.get().then((res: any) => {
      if (res?.wizard_completed) {
        setWizardCompleted(true);
      }
    }).catch(() => {});

    setSearchHealthLoading(true);
    searchHealthApi
      .getSummary(30)
      .then((res: any) => {
        if (res?.success && res?.data) {
          setSearchHealth(res.data as SearchHealthSummary);
          setSearchHealthError(false);
        }
      })
      .catch(() => {
        setSearchHealthError(true);
      })
      .finally(() => {
        setSearchHealthLoading(false);
      });
  }, []);

  const stats = useMemo(() => countFeatures(optionData), [optionData]);
  const recommendations = useMemo(() => getRecommendations(optionData), [optionData]);
  const securityItems = useMemo(() => getSecurityStatus(optionData), [optionData]);

  const recommendList = recommendations.filter((r) => r.type === "recommend");
  const cautionList = recommendations.filter((r) => r.type === "caution");

  const [snapshotCount, setSnapshotCount] = useState(() => {
    try {
      const stored = localStorage.getItem("mabox_snapshots");
      if (stored) {
        return JSON.parse(stored).length;
      }
    } catch (e) {
      return 0;
    }
    return 0;
  });

  const refreshPresets = useCallback(() => {
    setAllPresets(getAllPresets());
  }, []);

  const refreshSnapshots = useCallback(() => {
    setSnapshots(getSnapshots());
    setSnapshotCount(getSnapshots().length);
  }, []);

  const handleRestoreSnapshot = (snapshotId: string) => {
    confirm({
      title: "确认恢复快照？",
      icon: <ExclamationCircleOutlined />,
      content: "此操作将覆盖当前配置，请确认。",
      onOk: () => {
        const data = restoreSnapshot(snapshotId);
        if (data) {
          Object.keys(data).forEach((father) => {
            if (typeof data[father] === "object" && data[father] !== null) {
              Object.keys(data[father]).forEach((son) => {
                updateOption(father, son, data[father][son]);
              });
            }
          });
          message.success("快照已恢复，请点击保存按钮保存到服务器");
        } else {
          message.error("恢复快照失败");
        }
      },
    });
  };

  const handleDeleteSnapshot = (snapshotId: string) => {
    confirm({
      title: "确认删除此快照？",
      icon: <ExclamationCircleOutlined />,
      onOk: () => {
        deleteSnapshot(snapshotId);
        refreshSnapshots();
        message.success("已删除");
      },
    });
  };

  const handleRestoreDefault = () => {
    confirm({
      title: "确认恢复默认配置？",
      icon: <ExclamationCircleOutlined />,
      content: "此操作将重置所有设置为默认值，不可撤销。",
      onOk: () => {
        const defaultData = getDefaultConfig();
        Object.keys(defaultData).forEach((father) => {
          if (typeof defaultData[father] === "object" && defaultData[father] !== null) {
            Object.keys(defaultData[father]).forEach((son) => {
              updateOption(father, son, defaultData[father][son]);
            });
          }
        });
        message.success("已恢复默认配置，请点击保存按钮保存到服务器");
      },
    });
  };

  const handleExportReport = () => {
    if (!diagnosticSummary) {
      message.info("诊断数据加载中，请稍后再试");
      return;
    }

    const lines: string[] = [];
    lines.push("# WP Magick Toolbox 诊断报告");
    lines.push("");
    lines.push(`- 生成时间：${new Date().toLocaleString()}`);
    lines.push(`- 体检评分：${diagnosticSummary.score} / 100`);
    lines.push(`- 总体状态：${diagnosticSummary.status === "good" ? "优秀" : diagnosticSummary.status === "warning" ? "良好" : "需优化"}`);
    lines.push("");

    lines.push("## 环境信息");
    const envItem = diagnosticSummary.items?.find((i: DiagnosticItem) => i.id === "php_version");
    if (envItem) lines.push(`- PHP 版本：${envItem.message}`);
    const wpItem = diagnosticSummary.items?.find((i: DiagnosticItem) => i.id === "wp_version");
    if (wpItem) lines.push(`- WordPress 版本：${wpItem.message}`);
    const moduleItem = diagnosticSummary.items?.find((i: DiagnosticItem) => i.id === "module_count");
    if (moduleItem) lines.push(`- ${moduleItem.message}`);
    lines.push("");

    if (diagnosticSummary.risks && diagnosticSummary.risks.length > 0) {
      lines.push("## 风险模块/配置");
      diagnosticSummary.risks.forEach((risk: any) => {
        lines.push(`- **${risk.title}**（${risk.tier}）`);
        lines.push(`  ${risk.message}`);
      });
      lines.push("");
    }

    if (diagnosticSummary.recommendations && diagnosticSummary.recommendations.length > 0) {
      lines.push("## 关键建议");
      diagnosticSummary.recommendations.forEach((rec: any) => {
        lines.push(`- ${rec.title}：${rec.reason}`);
      });
      lines.push("");
    }

    if (diagnosticSummary.service_hints && diagnosticSummary.service_hints.length > 0) {
      lines.push("## 需要人工处理");
      diagnosticSummary.service_hints.forEach((hint: any) => {
        lines.push(`- ${hint.message}`);
      });
      lines.push("");
    }

    lines.push("---");
    lines.push("*本报告由 WP Magick Toolbox 体检中心自动生成*");
    lines.push("*报告不含任何 API Key、Secret 等敏感信息*");

    const reportText = lines.join("\n");

    navigator.clipboard.writeText(reportText).then(() => {
      message.success("诊断报告已复制到剪贴板");
      Modal.info({
        title: "导出报告并反馈",
        content: "诊断报告已复制到剪贴板，您可以将报告粘贴到反馈页面提交问题或建议。",
        okText: "前往反馈",
        onOk: () => {
          if (onNavigate) onNavigate("13", "");
        },
      });
    }).catch(() => {
      // 降级：下载为文件
      const blob = new Blob([reportText], { type: "text/markdown" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `mabox-diagnostic-report-${Date.now()}.md`;
      a.click();
      URL.revokeObjectURL(url);
      message.success("诊断报告已下载");
    });
  };

  const handleRestoreModuleDefault = (moduleKey: string) => {
    const source = serverDefaults || getDefaultConfig();
    const moduleDefault = source[moduleKey];

    if (!moduleDefault || typeof moduleDefault !== "object") {
      message.error(`模块 ${moduleKey} 没有可恢复的默认值`);
      return;
    }

    confirm({
      title: `恢复「${moduleKey}」模块默认值？`,
      icon: <ExclamationCircleOutlined />,
      content: `此操作将重置「${moduleKey}」模块为默认值，不影响其他模块。`,
      onOk: () => {
        Object.keys(moduleDefault).forEach((son) => {
          updateOption(moduleKey, son, moduleDefault[son]);
        });
        message.success(`已恢复「${moduleKey}」模块默认值，请点击保存按钮保存到服务器`);
      },
    });
  };

  const applyPreset = useCallback(
    async (presetId: string) => {
      const preset = allPresets.find((p: Preset) => p.id === presetId);
      if (!preset) return;

      confirm({
        title: `应用「${preset.name}」配置方案？`,
        icon: <ExclamationCircleOutlined />,
        content: (
          <div>
            <p>{preset.description}</p>
            <Text type="warning">此操作将覆盖当前部分配置，建议先导出备份。</Text>
          </div>
        ),
        okText: "确认应用",
        cancelText: "取消",
        onOk: async () => {
          setApplying(presetId);
          try {
            const merged = deepMerge(
              JSON.parse(JSON.stringify(optionData)),
              preset.config
            );

            Object.keys(preset.config).forEach((father) => {
              if (typeof merged[father] === "object" && merged[father] !== null) {
                Object.keys(merged[father]).forEach((son) => {
                  updateOption(father, son, merged[father][son]);
                });
              }
            });

            await saveOption(merged);
            message.success(`已应用预设：${preset.name}`);
          } catch (error) {
            message.error("应用预设失败，请重试");
          } finally {
            setApplying(null);
          }
        },
      });
    },
    [optionData, updateOption, allPresets]
  );

  const handleSavePreset = () => {
    if (!presetName.trim()) {
      message.error("请输入方案名称");
      return;
    }
    const id = `custom_${Date.now()}`;
    const success = saveCustomPreset({
      id,
      name: presetName,
      description: presetDesc || "自定义配置方案",
      config: JSON.parse(JSON.stringify(optionData)),
    });
    if (success) {
      message.success("自定义方案已保存");
      setSaveModalVisible(false);
      setPresetName("");
      setPresetDesc("");
      refreshPresets();
    } else {
      message.error("保存失败");
    }
  };

  const handleDeletePreset = (presetId: string) => {
    confirm({
      title: "确认删除此自定义方案？",
      icon: <ExclamationCircleOutlined />,
      onOk: () => {
        deleteCustomPreset(presetId);
        refreshPresets();
        message.success("已删除");
      },
    });
  };

  const handleRecommendationClick = (item: Recommendation) => {
    if (onNavigate) {
      onNavigate(item.tabKey, item.id);
    }
  };

const diagnosticScore = diagnosticSummary?.score ?? 60;
  const diagnosticStatus = diagnosticSummary?.status ?? "warning";

  const getScoreColor = (score: number) => {
    if (score >= 80) return "#52c41a";
    if (score >= 60) return "#faad14";
    return "#f5222d";
  };

  const getDiagnosticStatusText = (status: string) => {
    if (status === "good") return "优秀";
    if (status === "warning") return "良好";
    return "需优化";
  };

  const getDiagnosticStatusColor = (status: string) => {
    if (status === "good") return "success";
    if (status === "warning") return "warning";
    return "error";
  };

  const criticalItems = diagnosticSummary?.items?.filter((i: DiagnosticItem) => i.status === "critical") || [];
  const warningItems = diagnosticSummary?.items?.filter((i: DiagnosticItem) => i.status === "warning") || [];

  return (
    <div className="space-y-6">
      {!wizardCompleted && (
        <Alert
          message="欢迎使用 WP Magick Toolbox！"
          description="首次使用？让我们帮您快速配置推荐方案，3 分钟搞定核心设置。"
          type="info"
          showIcon
          icon={<RocketOutlined />}
          action={
            <Button type="primary" size="small" icon={<RocketOutlined />} onClick={() => setWizardVisible(true)}>
              开始配置
            </Button>
          }
          closable
          onClose={() => setWizardCompleted(true)}
        />
      )}
      {wizardCompleted && (
        <div style={{ textAlign: "right", marginBottom: -8 }}>
          <Button size="small" type="link" icon={<RocketOutlined />} onClick={() => setWizardVisible(true)}>
            配置向导
          </Button>
        </div>
      )}

      <WizardModal
        open={wizardVisible}
        onCancel={() => setWizardVisible(false)}
        onComplete={() => setWizardCompleted(true)}
        onNavigate={onNavigate}
      />
      {/* ===== 体检中心（后端诊断数据驱动） ===== */}
      <Card loading={diagnosticLoading}>
        <Row gutter={[24, 24]} align="middle">
          <Col xs={24} md={6}>
            <div style={{ textAlign: "center" }}>
              <Progress
                type="circle"
                percent={diagnosticScore}
                strokeColor={getScoreColor(diagnosticScore)}
                size={120}
                format={(percent) => (
                  <div>
                    <div style={{ fontSize: 28, fontWeight: "bold", color: getScoreColor(diagnosticScore) }}>
                      {percent}
                    </div>
                    <div style={{ fontSize: 12, color: "#999" }}>体检评分</div>
                  </div>
                )}
              />
              <div style={{ marginTop: 8 }}>
                <Tag color={getDiagnosticStatusColor(diagnosticStatus)}>
                  {getDiagnosticStatusText(diagnosticStatus)}
                </Tag>
              </div>
            </div>
          </Col>
          <Col xs={24} md={18}>
            <Row gutter={[16, 16]}>
              <Col span={8}>
                <Statistic
                  title="关键风险"
                  value={criticalItems.length}
                  valueStyle={{ color: criticalItems.length > 0 ? "#f5222d" : "#52c41a", fontSize: 24 }}
                />
              </Col>
              <Col span={8}>
                <Statistic
                  title="建议优化"
                  value={warningItems.length}
                  valueStyle={{ color: warningItems.length > 0 ? "#faad14" : "#52c41a", fontSize: 24 }}
                />
              </Col>
              <Col span={8}>
                <Statistic
                  title="建议开启"
                  value={diagnosticSummary?.recommendations?.length ?? recommendList.length}
                  valueStyle={{ fontSize: 24 }}
                />
              </Col>
              <Col span={24}>
                <div style={{ marginTop: 4 }}>
                  {diagnosticSummary?.risks && diagnosticSummary.risks.length > 0 ? (
                    <Text type="danger">
                      <ExclamationCircleOutlined style={{ marginRight: 4 }} />
                      检测到 {diagnosticSummary.risks.length} 个风险项，建议检查后再保存配置。
                    </Text>
                  ) : (
                    <Text type="success">
                      <SafetyOutlined style={{ marginRight: 4 }} />
                      当前未检测到配置风险，站点运行状态安全。
                    </Text>
                  )}
                </div>
                <div style={{ marginTop: 8 }}>
                  <Space wrap>
                    <Button
                      size="small"
                      icon={<FileTextOutlined />}
                      onClick={() => handleExportReport()}
                    >
                      导出诊断报告
                    </Button>
                    {diagnosticSummary?.service_hints && diagnosticSummary.service_hints.length > 0 && (
                      <Button
                        type="primary"
                        size="small"
                        icon={<CustomerServiceOutlined />}
                        onClick={() => onNavigate && onNavigate("13", "")}
                      >
                        联系技术支持
                      </Button>
                    )}
                  </Space>
                </div>
              </Col>
            </Row>
          </Col>
        </Row>
      </Card>

      {/* ===== 搜索健康摘要 ===== */}
      <Card
        title={<span><SearchOutlined style={{ marginRight: 8 }} />搜索健康</span>}
        loading={searchHealthLoading}
        extra={searchHealth && searchHealth.total_searches > 0 ? <Tag color="blue">近 {searchHealth.range_days} 天</Tag> : null}
      >
        {searchHealthError ? (
          <Alert type="warning" message="搜索健康数据加载失败" showIcon banner={false} style={{ marginBottom: 0 }} />
        ) : !searchHealth || searchHealth.total_searches === 0 ? (
          <div style={{ textAlign: "center", padding: "24px 0" }}>
            <SearchOutlined style={{ fontSize: 32, color: "#d9d9d9" }} />
            <div style={{ marginTop: 8, color: "#999" }}>暂无搜索数据</div>
            <div style={{ fontSize: 12, color: "#bbb" }}>开启搜索增强并积累搜索数据后，这里会展示搜索健康分析</div>
          </div>
        ) : (
          <>
            <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
              <Col span={8}>
                <Statistic title="总搜索量" value={searchHealth.total_searches} valueStyle={{ fontSize: 24 }} />
              </Col>
              <Col span={8}>
                <Statistic title="唯一关键词" value={searchHealth.unique_terms} valueStyle={{ fontSize: 24 }} />
              </Col>
              <Col span={8}>
                <Statistic
                  title="无结果词比例"
                  value={searchHealth.total_searches > 0 ? Math.round((searchHealth.no_result_terms.reduce((s, t) => s + t.no_result_count, 0) / searchHealth.total_searches) * 100) : 0}
                  suffix="%"
                  valueStyle={{ fontSize: 24, color: searchHealth.no_result_terms.length > 0 ? "#faad14" : "#52c41a" }}
                />
              </Col>
            </Row>
            <Row gutter={[16, 16]}>
              {searchHealth.top_terms.length > 0 && (
                <Col span={12}>
                  <Text strong style={{ marginBottom: 8, display: "block" }}>热门搜索词</Text>
                  <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
                    {searchHealth.top_terms.slice(0, 10).map((t) => (
                      <Tag key={t.term} color="blue">{t.term} ({t.count})</Tag>
                    ))}
                  </div>
                </Col>
              )}
              {searchHealth.no_result_terms.length > 0 && (
                <Col span={12}>
                  <Text strong style={{ marginBottom: 8, display: "block" }}>无结果搜索词</Text>
                  <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
                    {searchHealth.no_result_terms.slice(0, 10).map((t) => (
                      <Tag key={t.term} color="orange">{t.term} ({t.no_result_count})</Tag>
                    ))}
                  </div>
                </Col>
              )}
            </Row>
            {searchHealth.recommendations && searchHealth.recommendations.length > 0 && (
              <div style={{ marginTop: 12 }}>
                <Divider style={{ margin: "8px 0" }} />
                {searchHealth.recommendations.map((r) => (
                  <div key={r.id} style={{ marginBottom: 4 }}>
                    <Text type="warning"><ExclamationCircleOutlined style={{ marginRight: 4 }} />{r.title}：</Text>
                    <Text type="secondary">{r.reason}</Text>
                  </div>
                ))}
              </div>
            )}
          </>
        )}
      </Card>

      {/* ===== 功能统计 ===== */}
      <Card>
        <Row gutter={[16, 16]}>
          <Col span={6}>
            <Statistic
              title="总功能数"
              value={stats.total}
              valueStyle={{ fontSize: 24 }}
            />
          </Col>
          <Col span={6}>
            <Statistic
              title="已启用"
              value={stats.enabled}
              valueStyle={{ color: "#52c41a", fontSize: 24 }}
            />
          </Col>
          <Col span={6}>
            <Statistic
              title="已禁用"
              value={stats.disabled}
              valueStyle={{ color: "#f5222d", fontSize: 24 }}
            />
          </Col>
          <Col span={6}>
            <Statistic
              title="配置快照"
              value={snapshotCount}
              suffix="个"
              valueStyle={{ fontSize: 24 }}
            />
          </Col>
        </Row>
        <div style={{ marginTop: 12 }}>
          <Text type="secondary">
            <SafetyOutlined /> 安全状态：
            {securityItems.map((item: any, idx: number) => (
              <span key={item.label} style={{ marginLeft: 8 }}>
                <Badge
                  status={
                    item.status === "active"
                      ? "success"
                      : item.status === "partial"
                      ? "warning"
                      : "error"
                  }
                  text={item.label}
                />
                {idx < securityItems.length - 1 && <Divider type="vertical" />}
              </span>
            ))}
          </Text>
        </div>
      </Card>

      <FavoritesPanel optionData={optionData} />

      <Row gutter={[16, 16]}>
        <Col xs={24} lg={12}>
          <Card
            title={
              <span>
                <StarOutlined style={{ color: "#52c41a", marginRight: 8 }} />
                建议开启 ({recommendList.length}项)
              </span>
            }
          >
            <List
              size="small"
              dataSource={recommendList}
              renderItem={(item) => (
                <List.Item
                  actions={[
                    <Button
                      type="link"
                      size="small"
                      onClick={() => handleRecommendationClick(item)}
                    >
                      去开启 <ArrowRightOutlined />
                    </Button>,
                  ]}
                >
                  <List.Item.Meta
                    title={item.label}
                    description={
                      <Text type="secondary" style={{ fontSize: 12 }}>
                        {item.reason}
                      </Text>
                    }
                  />
                </List.Item>
              )}
              locale={{ emptyText: "暂无任何建议，您的配置已经很完善了！" }}
            />
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card
            title={
              <span>
                <ExclamationCircleOutlined style={{ color: "#faad14", marginRight: 8 }} />
                建议谨慎 ({cautionList.length}项)
              </span>
            }
          >
            <List
              size="small"
              dataSource={cautionList}
              renderItem={(item) => (
                <List.Item
                  actions={[
                    <Button
                      type="link"
                      size="small"
                      onClick={() => handleRecommendationClick(item)}
                    >
                      查看 <ArrowRightOutlined />
                    </Button>,
                  ]}
                >
                  <List.Item.Meta
                    title={
                      <span>
                        {item.label}
                        <Tag color="orange" style={{ marginLeft: 8, fontSize: 11 }}>
                          谨慎
                        </Tag>
                      </span>
                    }
                    description={
                      <Text type="secondary" style={{ fontSize: 12 }}>
                        {item.reason}
                      </Text>
                    }
                  />
                </List.Item>
              )}
              locale={{ emptyText: "暂无需要谨慎的功能，继续保持！" }}
            />
          </Card>
        </Col>
      </Row>

      <Card
        title={
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
            <span><ThunderboltOutlined style={{ marginRight: 8 }} />一键配置方案</span>
            <Space>
              <Button size="small" onClick={() => setBackupVisible(true)}>
                配置备份中心
              </Button>
              <Dropdown
                menu={{
                  items: [
                    { key: "optimize", label: "站点优化" },
                    { key: "page", label: "页面功能" },
                    { key: "function", label: "SEO / 功能" },
                    { key: "login", label: "登录页" },
                    { key: "domestic", label: "国内生态" },
                    { key: "performance", label: "性能优化" },
                    { key: "ai_review", label: "AI 审核" },
                    { key: "services", label: "增值服务" },
                    { key: "feedback", label: "用户反馈" },
                  ],
                  onClick: ({ key }) => handleRestoreModuleDefault(key),
                }}
              >
                <Button size="small">恢复模块默认值</Button>
              </Dropdown>
              <Button size="small" onClick={() => setSaveModalVisible(true)}>
                保存当前为方案
              </Button>
            </Space>
          </div>
        }
      >
        <Row gutter={[16, 16]}>
          {allPresets.map((preset: Preset) => {
            const isCustom = preset.id.startsWith("custom_");
            return (
              <Col xs={24} sm={12} md={8} lg={6} key={preset.id}>
                <Card className="h-full" size="small" hoverable>
                  <Space direction="vertical" className="w-full" size="small">
                    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                      <Title level={5} style={{ margin: 0 }}>{preset.name}</Title>
                      {isCustom && (
                        <Button
                          type="text"
                          size="small"
                          danger
                          onClick={() => handleDeletePreset(preset.id)}
                        >
                          删除
                        </Button>
                      )}
                    </div>
                    <Paragraph type="secondary" style={{ fontSize: 13, margin: 0, minHeight: 40 }}>
                      {preset.description}
                    </Paragraph>
                    <Button
                      type="primary"
                      block
                      loading={applying === preset.id}
                      onClick={() => applyPreset(preset.id)}
                      size="small"
                    >
                      应用此配置
                    </Button>
                  </Space>
                </Card>
              </Col>
            );
          })}
        </Row>
      </Card>

      <Modal
        title="配置备份中心"
        open={backupVisible}
        onCancel={() => setBackupVisible(false)}
        footer={null}
        width={700}
      >
        <Space direction="vertical" className="w-full" style={{ marginTop: 16 }}>
          <div style={{ marginBottom: 16 }}>
            <Button onClick={handleRestoreDefault} danger style={{ marginRight: 8 }}>
              恢复默认配置
            </Button>
            <Button onClick={() => { clearSnapshots(); refreshSnapshots(); message.success("已清除所有快照"); }}>
              清除所有快照
            </Button>
          </div>
          <List
            size="small"
            header={<Text strong>自动快照（最近5次保存）</Text>}
            dataSource={snapshots}
            renderItem={(item) => (
              <List.Item
                actions={[
                  <Button type="link" size="small" onClick={() => handleRestoreSnapshot(item.id)}>
                    恢复
                  </Button>,
                  <Button type="link" size="small" danger onClick={() => handleDeleteSnapshot(item.id)}>
                    删除
                  </Button>,
                ]}
              >
                <List.Item.Meta
                  title={item.dateStr}
                  description={`包含 ${Object.keys(item.data).length} 个模块`}
                />
              </List.Item>
            )}
            locale={{ emptyText: "暂无快照，保存配置后将自动生成" }}
          />
        </Space>
      </Modal>

      <Modal
        title="保存当前配置为自定义方案"
        open={saveModalVisible}
        onOk={handleSavePreset}
        onCancel={() => setSaveModalVisible(false)}
        okText="保存"
        cancelText="取消"
      >
        <Space direction="vertical" className="w-full" style={{ marginTop: 16 }}>
          <div>
            <Text>方案名称</Text>
            <Input
              placeholder="例如：我的博客配置"
              value={presetName}
              onChange={(e) => setPresetName(e.target.value)}
              style={{ marginTop: 8 }}
            />
          </div>
          <div>
            <Text>方案描述</Text>
            <Input.TextArea
              placeholder="描述此配置方案的用途..."
              value={presetDesc}
              onChange={(e) => setPresetDesc(e.target.value)}
              rows={3}
              style={{ marginTop: 8 }}
            />
          </div>
        </Space>
      </Modal>
    </div>
  );
};

export default Dashboard;
