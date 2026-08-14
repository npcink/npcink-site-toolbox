import React, { useState, useContext, useEffect, useCallback } from "react";
import { Form, Input, InputNumber, Select, Button, Card, Tag, Space, Typography } from "antd";
import { ReloadOutlined, ThunderboltOutlined, CheckCircleOutlined, CloseCircleOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { domesticApi } from "@/api";
import DiffModal from "@/components/diff-modal";
import { mergeEnvironmentProposal } from "@/components/domestic/environment-plan";
import { ModuleCard, DetailDrawer, ModuleRow, SecretField } from "@/components/settings-ui";
import type { DomesticLoginSecurity } from "@/tool/interface";
import { notice } from "@/tool/notice";
import { __, sprintf } from "@/tool/i18n";

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
  const { optionData, updateOption } = useContext(DataContext);
  const [results, setResults] = useState<Record<string, CheckResult> | null>(null);
  const [loading, setLoading] = useState(false);
  const [diffVisible, setDiffVisible] = useState(false);
  const [pendingDiffs, setPendingDiffs] = useState<any[]>([]);
  const [pendingProposed, setPendingProposed] = useState<Record<string, unknown> | null>(null);
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
        notice.error(__("检测失败，请重试"));
      }
    } catch (err) {
      notice.error(__("检测请求失败"));
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
      notice.info(__("所有服务可达，无需修复"));
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
        notice.error(__("获取修复建议失败"));
      }
    } catch (err) {
      notice.error(__("修复请求失败"));
    }
  }, [results]);

  const handleApplyFixes = useCallback(() => {
    if (!pendingProposed) return;
    try {
      const nextSite = mergeEnvironmentProposal(optionData.optimize.site, pendingProposed);
      updateOption("optimize", "site", nextSite);
      notice.success(__("修复建议已加入待保存更改，请使用全局保存按钮确认"));
      setDiffVisible(false);
      setPendingDiffs([]);
      setPendingProposed(null);
    } catch (err) {
      notice.error(err instanceof Error ? err.message : __("修复建议格式无效"));
    }
  }, [pendingProposed, optionData.optimize.site, updateOption]);

  return (
    <>
      <ModuleCard
        title={__("中国访问适配")}
        description={__("检测国外服务可达性并一键修复")}
        featureId="domestic-environment-check"
        tags={["推荐"]}
        switchable={false}
        actionLabel={__("检测")}
        onAction={handleOpenAndCheck}
        actionLoading={loading}
        aliases={["domestic-environment-gravatar", "domestic-environment-google_fonts", "domestic-environment-google_ajax"]}
      />
      <DetailDrawer
        title={__("中国访问适配")}
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        description={__("检测 Google Fonts、Gravatar 等服务在国内的可达性")}
      >
        <Space style={{ marginBottom: 16 }}>
          <Button size="small" icon={<ReloadOutlined />} onClick={handleCheck} loading={loading}>{__("检测")}</Button>
          {results && <Button type="primary" size="small" icon={<ThunderboltOutlined />} onClick={handleOneClickFix}>{__("生成修复建议")}</Button>}
        </Space>
        {results && !loading && (
          <div className="mabox-environment-results">
            {Object.entries(results).map(([key, result]) => (
              <Card size="small" className="mabox-environment-result-card" key={key}>
                <Text strong className="mabox-environment-result-service">{result.service}</Text>
                <div className="mabox-environment-result-status">
                  {result.reachable ? (
                    <Tag icon={<CheckCircleOutlined />} color="success">{__("可达")}</Tag>
                  ) : (
                    <Tag icon={<CloseCircleOutlined />} color="error">{__("不可达")}</Tag>
                  )}
                  {result.reachable && <Text type="secondary">{sprintf(__("延迟：%dms"), result.latency)}</Text>}
                </div>
                {!result.reachable && result.suggestion && (
                  <Text type="warning" className="mabox-environment-result-suggestion">
                    {result.suggestion}
                  </Text>
                )}
              </Card>
            ))}
          </div>
        )}
        <DiffModal
          visible={diffVisible}
          onCancel={() => { setDiffVisible(false); setPendingDiffs([]); setPendingProposed(null); }}
          onConfirm={handleApplyFixes}
          diffs={pendingDiffs}
          title={__("确认加入以下待保存更改？")}
          confirmText={__("加入待保存更改")}
        />
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
        title={__("备案与合规")}
        description={__("ICP 备案号、公安网备号、Cookie 同意弹窗")}
        featureId="domestic-compliance-icp_enabled"
        tags={["未配置"]}
        switchable={false}
        actionLabel={__("配置")}
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-compliance-icp", "domestic-compliance-police", "domestic-compliance-cookie", "domestic-compliance-copyright", "domestic-compliance-police_enabled", "domestic-compliance-cookie_enabled", "domestic-compliance-copyright_enabled"]}
      />
      <DetailDrawer title={__("备案与合规配置")} visible={drawerOpen} onClose={() => setDrawerOpen(false)} description={__("面向中国站长的备案与合规工具")}>
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title={__("启用 ICP 备案号显示")}
            featureId="domestic-compliance-icp_enabled"
            enabled={!!formData.icp_enabled}
            onChange={(checked: boolean) => onValuesChange({ icp_enabled: checked })}
          />
          <Form.Item label={__("ICP 备案号")} name="icp_number"><Input placeholder={__("如：京ICP备12345678号")} /></Form.Item>
          <Form.Item label={__("ICP 查询链接")} name="icp_link"><Input /></Form.Item>
          <ModuleRow
            title={__("公安网备号显示")}
            featureId="domestic-compliance-police_enabled"
            enabled={!!formData.police_enabled}
            onChange={(checked: boolean) => onValuesChange({ police_enabled: checked })}
          />
          <Form.Item label={__("公安网备号")} name="police_number"><Input placeholder={__("如：京公网安备11010102001234号")} /></Form.Item>
          <Form.Item label={__("网备查询链接")} name="police_link"><Input /></Form.Item>
          <ModuleRow
            title={__("Cookie 同意弹窗")}
            featureId="domestic-compliance-cookie_enabled"
            enabled={!!formData.cookie_enabled}
            onChange={(checked: boolean) => onValuesChange({ cookie_enabled: checked })}
          />
          <Form.Item label={__("Cookie 弹窗样式")} name="cookie_style"><Select options={[{ label: __("底部"), value: "bottom" }, { label: __("顶部"), value: "top" }]} /></Form.Item>
          <Form.Item label={__("Cookie 标题")} name="cookie_title"><Input /></Form.Item>
          <Form.Item label={__("Cookie 内容")} name="cookie_content"><TextArea rows={3} /></Form.Item>
          <Form.Item label={__("Cookie 按钮文字")} name="cookie_button"><Input /></Form.Item>
          <ModuleRow
            title={__("版权信息显示")}
            featureId="domestic-compliance-copyright_enabled"
            enabled={!!formData.copyright_enabled}
            onChange={(checked: boolean) => onValuesChange({ copyright_enabled: checked })}
          />
          <Form.Item label={__("版权信息 HTML")} name="copyright_html" extra={__("留空则使用默认版权格式")}><TextArea rows={3} placeholder={__("&copy; 2024 网站名称 版权所有")} /></Form.Item>
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
        title={__("微信生态")}
        description={__("JSSDK 分享、微信/QQ 打开引导")}
        featureId="domestic-wechat-jssdk_enabled"
        switchable={false}
        actionLabel={__("配置")}
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-wechat-jssdk", "domestic-wechat-guide"]}
      />
      <DetailDrawer title={__("微信生态配置")} visible={drawerOpen} onClose={() => setDrawerOpen(false)} description={__("微信生态增强功能")}>
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title={__("JSSDK 分享")}
            featureId="domestic-wechat-jssdk_enabled"
            enabled={!!formData.jssdk_enabled}
            onChange={(checked: boolean) => onValuesChange({ jssdk_enabled: checked })}
          />
          <Form.Item label="AppID" name="appid"><Input /></Form.Item>
          <SecretField label="AppSecret" path="domestic.wechat.appsecret" />
          <ModuleRow
            title={__("微信/QQ 打开引导")}
            featureId="domestic-wechat-guide_overlay"
            enabled={!!formData.guide_overlay_enabled}
            onChange={(checked: boolean) => onValuesChange({ guide_overlay_enabled: checked })}
          />
          <Form.Item label={__("引导处理方式")} name="guide_mode"><Select options={[{ label: __("仅提示"), value: "guide" }, { label: __("强制跳转"), value: "redirect" }]} /></Form.Item>
          <Form.Item label={__("引导文案")} name="guide_text"><Input /></Form.Item>
          <Form.Item label={__("二维码图片")} name="guide_qrcode"><Input placeholder={__("图片 URL，可选")} /></Form.Item>
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
        title={__("评论安全")}
        description={__("敏感词过滤、链接限制、IP 频率限制")}
        featureId="domestic-comment_security-blacklist_enabled"
        tags={["安全"]}
        switchable={false}
        actionLabel={__("配置")}
        onAction={() => setDrawerOpen(true)}
        aliases={["domestic-comment-blacklist", "domestic-comment-link-limit", "domestic-comment-ip-rate", "domestic-comment-nickname_filter", "domestic-comment-email_blacklist", "domestic-comment_security-duplicate_enabled", "domestic-comment_security-log_enabled"]}
      />
      <DetailDrawer title={__("评论安全配置")} visible={drawerOpen} onClose={() => setDrawerOpen(false)} description={__("评论安全中心，过滤垃圾评论")}>
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title={__("敏感词过滤")}
            featureId="domestic-comment_security-blacklist_enabled"
            enabled={!!formData.blacklist_enabled}
            onChange={(checked: boolean) => onValuesChange({ blacklist_enabled: checked })}
          />
          <Form.Item label={__("敏感词列表")} name="blacklist_words" extra={__("每行一个")}><TextArea rows={4} /></Form.Item>
          <Form.Item label={__("处理方式")} name="blacklist_action"><Select options={[{ label: __("拦截"), value: "block" }, { label: __("标记待审核"), value: "mark" }]} /></Form.Item>
          <ModuleRow
            title={__("评论链接限制")}
            description={__("限制评论中的最大链接数量")}
            featureId="domestic-comment_security-link_limit"
            enabled={!!formData.link_limit_enabled}
            onChange={(checked: boolean) => onValuesChange({ link_limit_enabled: checked })}
          />
          <Form.Item label={__("最大链接数")} name="link_limit_count"><InputNumber min={0} max={10} /></Form.Item>
          <ModuleRow
            title={__("重复评论拦截")}
            featureId="domestic-comment_security-duplicate_enabled"
            enabled={!!formData.duplicate_enabled}
            onChange={(checked: boolean) => onValuesChange({ duplicate_enabled: checked })}
          />
          <ModuleRow
            title={__("昵称过滤")}
            description={__("过滤包含敏感词的评论昵称")}
            featureId="domestic-comment_security-nickname_filter"
            enabled={!!formData.nickname_filter_enabled}
            onChange={(checked: boolean) => onValuesChange({ nickname_filter_enabled: checked })}
          />
          <Form.Item label={__("禁用昵称")} name="nickname_filter_words" extra={__("每行一个")}><TextArea rows={3} /></Form.Item>
          <ModuleRow
            title={__("邮箱域名黑名单")}
            featureId="domestic-comment_security-email_blacklist"
            enabled={!!formData.email_domain_enabled}
            onChange={(checked: boolean) => onValuesChange({ email_domain_enabled: checked })}
          />
          <Form.Item label={__("邮箱域名黑名单")} name="email_domain_blacklist" extra={__("每行一个")}><TextArea rows={3} /></Form.Item>
          <ModuleRow
            title={__("IP 频率限制")}
            description={__("限制同一 IP 的评论频率")}
            featureId="domestic-comment_security-ip_rate_limit"
            enabled={!!formData.ip_rate_enabled}
            onChange={(checked: boolean) => onValuesChange({ ip_rate_enabled: checked })}
          />
          <Form.Item label={__("IP 限制次数")} name="ip_rate_limit"><InputNumber min={1} max={100} /></Form.Item>
          <Form.Item label={__("IP 时间窗口(秒)")} name="ip_rate_window"><InputNumber min={10} max={3600} /></Form.Item>
          <ModuleRow
            title={__("记录拦截日志")}
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
  const publicData = optionData.domestic.login_security;
  const [formData, setFormData] = useState<DomesticLoginSecurity>(publicData);
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: Partial<DomesticLoginSecurity>) => {
    setFormData((previous) => ({ ...previous, ...changedValues }));
  };

  useEffect(() => { updateOption("domestic", "login_security", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title={__("登录安全")}
        description={__("管理登录尝试限制与匿名作者枚举")}
        featureId="domestic-login_security"
        tags={["安全"]}
        switchable={false}
        actionLabel={__("配置")}
        onAction={() => setDrawerOpen(true)}
      />
      <DetailDrawer
        title={__("登录安全设置")}
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        description={__("两项能力可独立启用，保存后才会在站点生效。")}
      >
        <Form labelCol={fromConfig.labelCol} wrapperCol={fromConfig.wrapperCol} style={{ maxWidth: fromConfig.maxWidth }} initialValues={publicData} onValuesChange={onValuesChange}>
          <ModuleRow
            title={__("登录尝试保护")}
            description={__("在统计窗口内限制同一已存在账号与来源 IP 组合的连续失败尝试")}
            featureId="domestic-login_security-attempt_limit_enabled"
            enabled={!!formData.attempt_limit_enabled}
            onChange={(checked: boolean) => onValuesChange({ attempt_limit_enabled: checked })}
          >
            <Form.Item label={__("失败尝试上限")} name="attempt_limit_count">
              <InputNumber min={2} max={20} precision={0} step={1} />
            </Form.Item>
            <Form.Item label={__("统计窗口（分钟）")} name="attempt_window_minutes">
              <InputNumber min={1} max={1440} precision={0} step={1} />
            </Form.Item>
            <Form.Item label={__("锁定时长（分钟）")} name="lock_duration_minutes">
              <InputNumber min={1} max={1440} precision={0} step={1} />
            </Form.Item>
            <details style={{ marginTop: 8 }}>
              <summary style={{ cursor: "pointer", fontWeight: 500 }}>{__("高级：可信代理")}</summary>
              <p style={{ color: "#666", margin: "8px 0 12px" }}>
                {__("仅在站点位于 CDN 或反向代理之后，且代理会覆盖 X-Forwarded-For 时填写。")}
                {__("留空时系统只信任 REMOTE_ADDR。")}
              </p>
              <Form.Item
                label={__("代理出口 IP")}
                name="trusted_proxies"
                extra={__("每行一个可信代理 IP；不要填写访客 IP 或整个公网网段。")}
              >
                <TextArea rows={3} placeholder={"203.0.113.10\n2001:db8::10"} />
              </Form.Item>
            </details>
          </ModuleRow>
          <ModuleRow
            title={__("限制匿名作者枚举")}
            description={__("收紧未登录访问中的作者参数与 REST 用户端点")}
            featureId="domestic-login_security-anonymous_author_guard_enabled"
            enabled={!!formData.anonymous_author_guard_enabled}
            onChange={(checked: boolean) => onValuesChange({ anonymous_author_guard_enabled: checked })}
          />
        </Form>
      </DetailDrawer>
    </>
  );
};

const App: React.FC<{ targetItemId?: string }> = ({ targetItemId }) => {
  const [envDrawerOpen, setEnvDrawerOpen] = useState(false);
  const [complianceDrawerOpen, setComplianceDrawerOpen] = useState(false);
  const [wechatDrawerOpen, setWechatDrawerOpen] = useState(false);
  const [commentDrawerOpen, setCommentDrawerOpen] = useState(false);
  const [loginDrawerOpen, setLoginDrawerOpen] = useState(false);

  useEffect(() => {
    if (!targetItemId) return;
    if (targetItemId.startsWith("domestic-environment-")) setEnvDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-compliance-")) setComplianceDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-wechat-")) setWechatDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-comment")) setCommentDrawerOpen(true);
    else if (targetItemId.startsWith("domestic-login")) setLoginDrawerOpen(true);
  }, [targetItemId]);

  return (
    <div className="mabox-module-grid">
      <EnvironmentCard drawerOpen={envDrawerOpen} onDrawerOpenChange={setEnvDrawerOpen} />
      <ComplianceCard drawerOpen={complianceDrawerOpen} onDrawerOpenChange={setComplianceDrawerOpen} />
      <WechatCard drawerOpen={wechatDrawerOpen} onDrawerOpenChange={setWechatDrawerOpen} />
      <CommentSecurityCard drawerOpen={commentDrawerOpen} onDrawerOpenChange={setCommentDrawerOpen} />
      <LoginSecurityCard drawerOpen={loginDrawerOpen} onDrawerOpenChange={setLoginDrawerOpen} />
    </div>
  );
};

export default App;
