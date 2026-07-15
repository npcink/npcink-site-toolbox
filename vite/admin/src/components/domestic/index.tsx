import React, { useState, useContext, useEffect, useCallback } from "react";
import { Form, Input, InputNumber, Select, Button, Card, Row, Col, Tag, Space, Typography, message } from "antd";
import { ReloadOutlined, ThunderboltOutlined, CheckCircleOutlined, CloseCircleOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { domesticApi, runBaiduBatchPush } from "@/api";
import DiffModal from "@/components/diff-modal";
import { createSnapshot } from "@/tool/snapshot";
import { saveOption } from "@/axios/save";
import { ModuleCard, DetailDrawer, RiskNotice, ModuleRow } from "@/components/settings-ui";

const fromConfig = AntConfig.from;
const { TextArea } = Input;
const { Text } = Typography;

interface CheckResult {
  service: string;
  reachable: boolean;
  latency: number;
  suggestion: string;
}

const EnvironmentCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, refreshOption } = useContext(DataContext);
  const [results, setResults] = useState<Record<string, CheckResult> | null>(null);
  const [loading, setLoading] = useState(false);
  const [diffVisible, setDiffVisible] = useState(false);
  const [pendingDiffs, setPendingDiffs] = useState<any[]>([]);
  const [pendingProposed, setPendingProposed] = useState<Record<string, any> | null>(null);
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const handleCheck = useCallback(async () => {
    setLoading(true);
    try {
      const res = await domesticApi.checkEnvironment();
      if (res?.success && res?.data) {
        setResults(res.data);
      } else {
        message.error("检测失败，请重试");
      }
    } catch (err) {
      message.error("检测请求失败");
    } finally {
      setLoading(false);
    }
  }, []);

  const handleOpenAndCheck = useCallback(() => {
    setDrawerOpen(true);
    handleCheck();
  }, [handleCheck]);

  const handleOneClickFix = useCallback(async () => {
    if (!results) return;
    const unreachable = Object.entries(results)
      .filter(([_, r]) => !r.reachable)
      .map(([key]) => key)
      .filter((key) => ["gravatar", "google_fonts", "google_ajax"].includes(key));
    if (unreachable.length === 0) {
      message.info("所有服务可达，无需修复");
      return;
    }
    try {
      const res = await domesticApi.applyEnvironmentFix(unreachable);
      if (res?.success && res?.data?.diffs) {
        setPendingDiffs(
          res.data.diffs.map((d: any) => ({
            path: `optimize.site.${d.key}`,
            label: d.label,
            module: "optimize",
            before: d.before,
            after: d.after,
            riskLevel: d.risk_level === "high" ? "high" : ("none" as const),
          }))
        );
        if (res.data.proposed) setPendingProposed(res.data.proposed);
        setDiffVisible(true);
      } else {
        message.error("获取修复建议失败");
      }
    } catch (err) {
      message.error("修复请求失败");
    }
  }, [results]);

  const handleApplyFixes = useCallback(async () => {
    if (!pendingProposed) return;
    try {
      const merged: any = JSON.parse(JSON.stringify(optionData));
      if (!merged.optimize) merged.optimize = {};
      if (!merged.optimize.site) merged.optimize.site = {};
      Object.entries(pendingProposed).forEach(([key, value]) => {
        merged.optimize.site[key] = value;
      });
      createSnapshot(optionData);
      await saveOption(merged);
      await refreshOption();
      message.success("已应用修复");
      setDiffVisible(false);
      setPendingDiffs([]);
      setPendingProposed(null);
      handleCheck();
    } catch (err) {
      message.error("修复请求失败");
    }
  }, [pendingProposed, optionData, refreshOption, handleCheck]);

  return (
    <>
      <ModuleCard
        title="中国访问适配"
        description="检测国外服务可达性并一键修复"
        featureId="domestic-environment-check"
        tags={["推荐"]}
        switchable={false}
        actionLabel="检测"
        onAction={handleOpenAndCheck}
        actionLoading={loading}
        aliases={["domestic-environment-gravatar", "domestic-environment-google_fonts", "domestic-environment-google_ajax"]}
      />
      <DetailDrawer
        title="中国访问适配"
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        description="检测 Google Fonts、Gravatar 等服务在国内的可达性"
      >
        <Space style={{ marginBottom: 16 }}>
          <Button size="small" icon={<ReloadOutlined />} onClick={handleCheck} loading={loading}>检测</Button>
          {results && <Button type="primary" size="small" icon={<ThunderboltOutlined />} onClick={handleOneClickFix}>一键修复</Button>}
        </Space>
        {results && !loading && (
          <Row gutter={[16, 16]}>
            {Object.entries(results).map(([key, result]) => (
              <Col xs={24} sm={12} md={8} key={key}>
                <Card size="small">
                  <Space direction="vertical" style={{ width: "100%" }} size="small">
                    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                      <Text strong>{result.service}</Text>
                      {result.reachable ? (
                        <Tag icon={<CheckCircleOutlined />} color="success">可达</Tag>
                      ) : (
                        <Tag icon={<CloseCircleOutlined />} color="error">不可达</Tag>
                      )}
                    </div>
                    {result.reachable && <Text type="secondary" style={{ fontSize: 12 }}>延迟：{result.latency}ms</Text>}
                    {!result.reachable && result.suggestion && <Text type="warning" style={{ fontSize: 12 }}>{result.suggestion}</Text>}
                  </Space>
                </Card>
              </Col>
            ))}
          </Row>
        )}
        <DiffModal visible={diffVisible} onCancel={() => { setDiffVisible(false); setPendingDiffs([]); setPendingProposed(null); }} onConfirm={handleApplyFixes} diffs={pendingDiffs} />
      </DetailDrawer>
    </>
  );
};

const ComplianceCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.compliance || {};
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("domestic", "compliance", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title="备案与合规"
        description="ICP 备案号、公安网备号、Cookie 同意弹窗"
        featureId="domestic-compliance-icp_enabled"
        tags={["未配置"]}
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-compliance-icp", "domestic-compliance-police", "domestic-compliance-cookie", "domestic-compliance-copyright", "domestic-compliance-police_enabled", "domestic-compliance-cookie_enabled", "domestic-compliance-copyright_enabled"]}
      />
      <DetailDrawer title="备案与合规配置" visible={drawerOpen} onClose={() => setDrawerOpen(false)} description="面向中国站长的备案与合规工具">
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title="启用 ICP 备案号显示"
            featureId="domestic-compliance-icp_enabled"
            enabled={!!formData.icp_enabled}
            onChange={(checked: boolean) => onValuesChange({ icp_enabled: checked })}
          />
          <Form.Item label="ICP 备案号" name="icp_number"><Input placeholder="如：京ICP备12345678号" /></Form.Item>
          <Form.Item label="ICP 查询链接" name="icp_link"><Input /></Form.Item>
          <ModuleRow
            title="公安网备号显示"
            featureId="domestic-compliance-police_enabled"
            enabled={!!formData.police_enabled}
            onChange={(checked: boolean) => onValuesChange({ police_enabled: checked })}
          />
          <Form.Item label="公安网备号" name="police_number"><Input placeholder="如：京公网安备11010102001234号" /></Form.Item>
          <Form.Item label="网备查询链接" name="police_link"><Input /></Form.Item>
          <ModuleRow
            title="Cookie 同意弹窗"
            featureId="domestic-compliance-cookie_enabled"
            enabled={!!formData.cookie_enabled}
            onChange={(checked: boolean) => onValuesChange({ cookie_enabled: checked })}
          />
          <Form.Item label="Cookie 弹窗样式" name="cookie_style"><Select options={[{ label: "底部", value: "bottom" }, { label: "顶部", value: "top" }]} /></Form.Item>
          <Form.Item label="Cookie 标题" name="cookie_title"><Input /></Form.Item>
          <Form.Item label="Cookie 内容" name="cookie_content"><TextArea rows={3} /></Form.Item>
          <Form.Item label="Cookie 按钮文字" name="cookie_button"><Input /></Form.Item>
          <ModuleRow
            title="版权信息显示"
            featureId="domestic-compliance-copyright_enabled"
            enabled={!!formData.copyright_enabled}
            onChange={(checked: boolean) => onValuesChange({ copyright_enabled: checked })}
          />
          <Form.Item label="版权信息 HTML" name="copyright_html" extra="留空则使用默认版权格式"><TextArea rows={3} placeholder="&copy; 2024 网站名称 版权所有" /></Form.Item>
        </Form>
      </DetailDrawer>
    </>
  );
};

const BaiduPushCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.baidu_push || {};
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;
  const [pushing, setPushing] = useState(false);

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("domestic", "baidu_push", formData); }, [formData]);

  const handleBatchPush = async () => {
    setPushing(true);
    try {
      const response = await runBaiduBatchPush((offset) => domesticApi.baiduPush(undefined, offset));
      if (response.success) message.success(response.data?.message || "批量推送完成");
    } catch (error) {
      message.error(error instanceof Error ? error.message : "推送失败");
    } finally {
      setPushing(false);
    }
  };

  return (
    <>
      <ModuleCard
        title="百度推送"
        description="文章发布自动推送到百度搜索资源平台"
        featureId="domestic-baidu_push-active_push_enabled"
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-baidu-push", "domestic-baidu_push-auto_push_enabled", "domestic-baidu_push-batch_push", "domestic-baidu_push-batch_push_enabled"]}
      />
      <DetailDrawer title="百度推送配置" visible={drawerOpen} onClose={() => setDrawerOpen(false)} description="主动推送和自动推送文章到百度">
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title="主动推送"
            featureId="domestic-baidu_push-active_push_enabled"
            enabled={!!formData.active_push_enabled}
            onChange={(checked: boolean) => onValuesChange({ active_push_enabled: checked })}
          />
          <Form.Item label="Site" name="site"><Input placeholder="如：https://www.example.com" /></Form.Item>
          <Form.Item label="Token" name="token"><Input placeholder="百度搜索资源平台提供的 Token" /></Form.Item>
          <ModuleRow
            title="自动推送 JS"
            description="在页面底部插入百度自动推送代码"
            featureId="domestic-baidu_push-auto_push_enabled"
            enabled={!!formData.auto_push_enabled}
            onChange={(checked: boolean) => onValuesChange({ auto_push_enabled: checked })}
          />
          <ModuleRow
            title="批量推送入口"
            featureId="domestic-baidu_push-batch_push_enabled"
            enabled={!!formData.batch_push_enabled}
            onChange={(checked: boolean) => onValuesChange({ batch_push_enabled: checked })}
          />
          {formData.batch_push_enabled && (
            <Form.Item label="批量推送"><Button type="primary" onClick={handleBatchPush} loading={pushing}>开始批量推送</Button></Form.Item>
          )}
        </Form>
      </DetailDrawer>
    </>
  );
};

const WechatCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.wechat || {};
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("domestic", "wechat", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title="微信生态"
        description="JSSDK 分享、微信/QQ 打开引导"
        featureId="domestic-wechat-jssdk_enabled"
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-wechat-jssdk", "domestic-wechat-guide"]}
      />
      <DetailDrawer title="微信生态配置" visible={drawerOpen} onClose={() => setDrawerOpen(false)} description="微信生态增强功能">
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title="JSSDK 分享"
            featureId="domestic-wechat-jssdk_enabled"
            enabled={!!formData.jssdk_enabled}
            onChange={(checked: boolean) => onValuesChange({ jssdk_enabled: checked })}
          />
          <Form.Item label="AppID" name="appid"><Input /></Form.Item>
          <Form.Item label="AppSecret" name="appsecret"><Input /></Form.Item>
          <ModuleRow
            title="微信/QQ 打开引导"
            featureId="domestic-wechat-guide_overlay"
            enabled={!!formData.guide_overlay_enabled}
            onChange={(checked: boolean) => onValuesChange({ guide_overlay_enabled: checked })}
          />
          <Form.Item label="引导处理方式" name="guide_mode"><Select options={[{ label: "仅提示", value: "guide" }, { label: "强制跳转", value: "redirect" }]} /></Form.Item>
          <Form.Item label="引导文案" name="guide_text"><Input /></Form.Item>
          <Form.Item label="二维码图片" name="guide_qrcode"><Input placeholder="图片 URL，可选" /></Form.Item>
        </Form>
      </DetailDrawer>
    </>
  );
};

const CommentSecurityCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.comment_security || {};
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("domestic", "comment_security", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title="评论安全"
        description="敏感词过滤、链接限制、IP 频率限制"
        featureId="domestic-comment_security-blacklist_enabled"
        tags={["安全"]}
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-comment-blacklist", "domestic-comment-link-limit", "domestic-comment-ip-rate", "domestic-comment-nickname_filter", "domestic-comment-email_blacklist", "domestic-comment_security-duplicate_enabled", "domestic-comment_security-log_enabled"]}
      />
      <DetailDrawer title="评论安全配置" visible={drawerOpen} onClose={() => setDrawerOpen(false)} description="评论安全中心，过滤垃圾评论">
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title="敏感词过滤"
            featureId="domestic-comment_security-blacklist_enabled"
            enabled={!!formData.blacklist_enabled}
            onChange={(checked: boolean) => onValuesChange({ blacklist_enabled: checked })}
          />
          <Form.Item label="敏感词列表" name="blacklist_words" extra="每行一个"><TextArea rows={4} /></Form.Item>
          <Form.Item label="处理方式" name="blacklist_action"><Select options={[{ label: "拦截", value: "block" }, { label: "标记待审核", value: "mark" }]} /></Form.Item>
          <ModuleRow
            title="评论链接限制"
            description="限制评论中的最大链接数量"
            featureId="domestic-comment_security-link_limit"
            enabled={!!formData.link_limit_enabled}
            onChange={(checked: boolean) => onValuesChange({ link_limit_enabled: checked })}
          />
          <Form.Item label="最大链接数" name="link_limit_count"><InputNumber min={0} max={10} /></Form.Item>
          <ModuleRow
            title="重复评论拦截"
            featureId="domestic-comment_security-duplicate_enabled"
            enabled={!!formData.duplicate_enabled}
            onChange={(checked: boolean) => onValuesChange({ duplicate_enabled: checked })}
          />
          <ModuleRow
            title="昵称过滤"
            description="过滤包含敏感词的评论昵称"
            featureId="domestic-comment_security-nickname_filter"
            enabled={!!formData.nickname_filter_enabled}
            onChange={(checked: boolean) => onValuesChange({ nickname_filter_enabled: checked })}
          />
          <Form.Item label="禁用昵称" name="nickname_filter_words" extra="每行一个"><TextArea rows={3} /></Form.Item>
          <ModuleRow
            title="邮箱域名黑名单"
            featureId="domestic-comment_security-email_blacklist"
            enabled={!!formData.email_domain_enabled}
            onChange={(checked: boolean) => onValuesChange({ email_domain_enabled: checked })}
          />
          <Form.Item label="邮箱域名黑名单" name="email_domain_blacklist" extra="每行一个"><TextArea rows={3} /></Form.Item>
          <ModuleRow
            title="IP 频率限制"
            description="限制同一 IP 的评论频率"
            featureId="domestic-comment_security-ip_rate_limit"
            enabled={!!formData.ip_rate_enabled}
            onChange={(checked: boolean) => onValuesChange({ ip_rate_enabled: checked })}
          />
          <Form.Item label="IP 限制次数" name="ip_rate_limit"><InputNumber min={1} max={100} /></Form.Item>
          <Form.Item label="IP 时间窗口(秒)" name="ip_rate_window"><InputNumber min={10} max={3600} /></Form.Item>
          <ModuleRow
            title="记录拦截日志"
            featureId="domestic-comment_security-log_enabled"
            enabled={!!formData.log_enabled}
            onChange={(checked: boolean) => onValuesChange({ log_enabled: checked })}
          />
        </Form>
      </DetailDrawer>
    </>
  );
};

const LoginSecurityCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.login_security || {};
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("domestic", "login_security", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title="登录安全"
        description="暴力破解防护、自定义登录地址、IP 白名单"
        featureId="domestic-login_security-fail_limit_enabled"
        tags={["安全"]}
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-login-fail-limit", "domestic-login-custom-url", "domestic-login-ip-whitelist", "domestic-login-ip_lock", "domestic-login-login_notify", "domestic-login-login_log", "domestic-login_security-ban_enumeration_enabled"]}
      />
      <DetailDrawer title="登录安全配置" visible={drawerOpen} onClose={() => setDrawerOpen(false)} description="登录安全中心，防止暴力破解">
        <RiskNotice warning="自定义登录地址和 IP 限制可能导致管理员无法登录" suggestion="建议先配置 IP 白名单再开启限制" />
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title="登录失败限制"
            featureId="domestic-login_security-fail_limit_enabled"
            enabled={!!formData.fail_limit_enabled}
            onChange={(checked: boolean) => onValuesChange({ fail_limit_enabled: checked })}
          />
          <Form.Item label="最大失败次数" name="fail_limit_count"><InputNumber min={3} max={20} /></Form.Item>
          <Form.Item label="锁定时间(分钟)" name="fail_lock_duration"><InputNumber min={5} max={1440} /></Form.Item>
          <ModuleRow
            title="IP 失败锁定"
            featureId="domestic-login_security-ip_lock_enabled"
            enabled={!!formData.ip_lock_enabled}
            onChange={(checked: boolean) => onValuesChange({ ip_lock_enabled: checked })}
          />
          <Form.Item label="IP 失败锁定次数" name="ip_lock_count"><InputNumber min={5} max={50} /></Form.Item>
          <Form.Item label="IP 锁定时间(分钟)" name="ip_lock_duration"><InputNumber min={5} max={1440} /></Form.Item>
          <ModuleRow
            title="自定义登录地址"
            description="隐藏默认 wp-login.php 登录入口"
            featureId="domestic-login_security-custom_login_enabled"
            enabled={!!formData.custom_login_enabled}
            onChange={(checked: boolean) => onValuesChange({ custom_login_enabled: checked })}
          />
          <Form.Item label="登录 slug" name="custom_login_slug" extra="如：my-login"><Input /></Form.Item>
          <ModuleRow
            title="禁止用户名枚举"
            featureId="domestic-login_security-ban_enumeration_enabled"
            enabled={!!formData.ban_enumeration_enabled}
            onChange={(checked: boolean) => onValuesChange({ ban_enumeration_enabled: checked })}
          />
          <ModuleRow
            title="登录通知"
            description="登录成功时发送邮件通知管理员"
            featureId="domestic-login_security-login_notify_enabled"
            enabled={!!formData.login_notify_enabled}
            onChange={(checked: boolean) => onValuesChange({ login_notify_enabled: checked })}
          />
          <ModuleRow
            title="登录日志"
            description="记录所有登录尝试"
            featureId="domestic-login_security-login_log_enabled"
            enabled={!!formData.login_log_enabled}
            onChange={(checked: boolean) => onValuesChange({ login_log_enabled: checked })}
          />
          <ModuleRow
            title="IP 白名单"
            description="仅允许白名单 IP 访问后台"
            featureId="domestic-login_security-ip_whitelist_enabled"
            enabled={!!formData.ip_whitelist_enabled}
            onChange={(checked: boolean) => onValuesChange({ ip_whitelist_enabled: checked })}
          />
          <Form.Item label="白名单 IP" name="ip_whitelist" extra="每行一个"><TextArea rows={4} /></Form.Item>
        </Form>
      </DetailDrawer>
    </>
  );
};

const App: React.FC<{ targetItemId?: string }> = ({ targetItemId }) => {
  const [envDrawerOpen, setEnvDrawerOpen] = useState(false);
  const [complianceDrawerOpen, setComplianceDrawerOpen] = useState(false);
  const [baiduDrawerOpen, setBaiduDrawerOpen] = useState(false);
  const [wechatDrawerOpen, setWechatDrawerOpen] = useState(false);
  const [commentDrawerOpen, setCommentDrawerOpen] = useState(false);
  const [loginDrawerOpen, setLoginDrawerOpen] = useState(false);

  useEffect(() => {
    if (!targetItemId) return;
    if (targetItemId.startsWith("domestic-environment-")) setEnvDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-compliance-")) setComplianceDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-baidu")) setBaiduDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-wechat-")) setWechatDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-comment")) setCommentDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-login")) setLoginDrawerOpen(true);
  }, [targetItemId]);

  return (
    <div className="mabox-module-grid">
      <EnvironmentCard drawerOpen={envDrawerOpen} onDrawerOpenChange={setEnvDrawerOpen} />
      <ComplianceCard drawerOpen={complianceDrawerOpen} onDrawerOpenChange={setComplianceDrawerOpen} />
      <BaiduPushCard drawerOpen={baiduDrawerOpen} onDrawerOpenChange={setBaiduDrawerOpen} />
      <WechatCard drawerOpen={wechatDrawerOpen} onDrawerOpenChange={setWechatDrawerOpen} />
      <CommentSecurityCard drawerOpen={commentDrawerOpen} onDrawerOpenChange={setCommentDrawerOpen} />
      <LoginSecurityCard drawerOpen={loginDrawerOpen} onDrawerOpenChange={setLoginDrawerOpen} />
    </div>
  );
};

export default App;
