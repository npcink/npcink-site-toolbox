import React from "react";
import { Typography, List, Tag, Button, Space, Divider } from "antd";
import { ArrowLeftOutlined, ThunderboltOutlined } from "@ant-design/icons";
import { Preset } from "@/tool/presets";
import { diffConfig } from "@/tool/diff";

const { Title, Text, Paragraph } = Typography;

interface StepPreviewProps {
  preset: Preset;
  currentConfig: Record<string, any>;
  onApply: () => void;
  onBack: () => void;
  applying: boolean;
}

function flattenConfigKeys(config: Record<string, any>, prefix = ""): string[] {
  const keys: string[] = [];
  for (const [key, value] of Object.entries(config)) {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === "object" && !Array.isArray(value)) {
      keys.push(...flattenConfigKeys(value, path));
    } else {
      keys.push(path);
    }
  }
  return keys;
}

const LABEL_MAP: Record<string, string> = {
  "optimize.site.no_escape": "禁止转义",
  "optimize.site.hide_top_toolbar": "隐藏顶部工具条",
  "optimize.site.remove_RSS_version": "移除 WP 版本号",
  "optimize.site.cdn_replace": "CDN 替换",
  "optimize.site.cdn_gravatar": "Gravatar 头像替换",
  "optimize.site.cdn_google_fonts": "Google Fonts 替换",
  "optimize.site.remove_sitemap_users": "移除 sitemap 用户",
  "optimize.site.hide_email_ip": "隐藏邮件 IP",
  "optimize.medium.img_add_tag": "图片 Alt 自动补全",
  "optimize.medium.upload_auto_name": "图片自动重命名",
  "page.comment.interval": "评论间隔限制",
  "page.comment.words_number": "评论字数限制",
  "page.comment.sensitive_words": "敏感词过滤",
  "page.comment.baidu_moderation": "百度评论审核",
  "page.function.add_last_update": "文章更新时间",
  "page.function.search_limit": "搜索频次限制",
  "page.function.share": "文章分享",
  "page.function.login_search": "仅登录可搜索",
  "page.function.anti_crawler": "防爬虫",
  "page.feature.page_scrolling": "平滑滚动",
  "page.feature.reading_progress": "阅读进度条",
  "page.feature.top_loading": "顶部加载条",
  "page.jurisdiction.ban_copy": "禁止复制",
  "page.jurisdiction.ban_open_weixing": "禁止微信打开",
  "function.seo.seo_home": "首页 TDK",
  "function.seo.seo_single": "文章页 TDK",
  "function.seo.seo_category": "分类页 TDK",
  "function.seo.seo_tag": "标签页 TDK",
  "login.security.login_code": "登录验证码",
  "domestic.baidu_push.active_push_enabled": "百度主动推送",
  "domestic.baidu_push.auto_push_enabled": "百度自动推送",
};

function getLabel(path: string): string {
  return LABEL_MAP[path] || path.split(".").pop() || path;
}

const StepPreview: React.FC<StepPreviewProps> = ({ preset, currentConfig, onApply, onBack, applying }) => {
  const newKeys = flattenConfigKeys(preset.config);
  const enabledItems = newKeys.filter((key) => {
    const value = getNestedValue(preset.config, key);
    return value === true || (typeof value === "string" && value !== "false" && value !== "");
  });

  const diffItems = diffConfig(currentConfig, preset.config);

  const highRiskChanges = diffItems.filter((d: any) => d.riskLevel === "high");

  function getNestedValue(obj: any, path: string): any {
    const keys = path.split(".");
    let current = obj;
    for (const k of keys) {
      if (current && typeof current === "object" && k in current) {
        current = current[k];
      } else {
        return undefined;
      }
    }
    return current;
  }

  return (
    <div>
      <Title level={4} style={{ textAlign: "center" }}>
        {preset.icon} {preset.name}
      </Title>
      <Paragraph type="secondary" style={{ textAlign: "center" }}>
        {preset.description} · 将启用 {enabledItems.length} 个核心功能
      </Paragraph>

      <Divider>将启用的功能</Divider>

      <List
        size="small"
        dataSource={enabledItems}
        renderItem={(key) => (
          <List.Item>
            <Text>{getLabel(key)}</Text>
            <Tag color="green" style={{ marginLeft: 8 }}>开启</Tag>
          </List.Item>
        )}
        locale={{ emptyText: "无新开启功能" }}
      />

      {highRiskChanges.length > 0 && (
        <>
          <Divider>⚠️ 高风险变更</Divider>
          <List
            size="small"
            dataSource={highRiskChanges}
            renderItem={(d: any) => (
              <List.Item>
                <Text type="danger">{d.label}：{String(d.before)} → {String(d.after)}</Text>
              </List.Item>
            )}
          />
        </>
      )}

      <div style={{ marginTop: 24, textAlign: "center" }}>
        <Space>
          <Button icon={<ArrowLeftOutlined />} onClick={onBack}>
            重新选择
          </Button>
          <Button
            type="primary"
            icon={<ThunderboltOutlined />}
            loading={applying}
            onClick={onApply}
          >
            应用此方案
          </Button>
        </Space>
        <Text type="secondary" style={{ display: "block", marginTop: 8, fontSize: 12 }}>
          应用后可随时在 Dashboard 修改配置
        </Text>
      </div>
    </div>
  );
};

export default StepPreview;