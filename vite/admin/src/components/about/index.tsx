import { Typography } from "antd";
import { SettingsSection, SettingsTabs } from "@/components/settings-ui";
import Source from "@/components/about/table";
import AiDiagnostics from "@/components/about/ai-diagnostics";
import RuntimeStatus from "@/components/about/runtime-status";
import { __ } from "@/tool/i18n";

const { Paragraph, Link } = Typography;

const UsageHelp = () => (
  <>
    <SettingsSection title={__("保存与离开保护")}>
      <Paragraph>
        {__("修改普通设置或填写凭据后，页面会显示待保存数量。刷新、关闭标签页或切换到插件内其他区域时，系统会先确认，避免静默丢失草稿。")}
      </Paragraph>
      <Paragraph>
        {__("保存请求带有读取设置时的配置版本。如果其他管理员、另一个标签页或受控代码已经更新设置，本次保存会停止并提示重新读取，不会用旧快照覆盖新配置。")}
      </Paragraph>
    </SettingsSection>
    <SettingsSection title={__("发生保存冲突时")}>
      <Paragraph>
        {__("先记下当前准备修改的项目，再重新读取或刷新后台。核对最新配置和保存差异后，再提交需要保留的修改。")}
      </Paragraph>
    </SettingsSection>
    <SettingsSection title={__("数据库清理")}>
      <Paragraph>
        {__("数据库清理必须按项目先预览、再确认。预览资格仅对当前管理员和当前清理项目有效，5 分钟后失效且只能使用一次；如果执行前数据库内容发生变化，系统会停止清理并要求重新预览。")}
      </Paragraph>
    </SettingsSection>
    <SettingsSection title={__("图片 Alt 检查")}>
      <Paragraph>
        {__("SEO 检查助手和媒体库体检只检查缺少 Alt 的图片并显示数量。为避免与站点已有 SEO 或媒体插件重复写入，Npcink Site Toolbox 不提供 Alt 自动写入；请在负责 SEO 的插件或媒体库中人工处理。")}
      </Paragraph>
    </SettingsSection>
  </>
);

const AboutPlugin = () => (
  <div>
    <Paragraph>
      这是一款完全免费且开源的插件，还在根据各位的使用和反馈，不断优化和增添新功能中。
    </Paragraph>
    <Paragraph>
      <ul style={{ paddingLeft: 20 }}>
        <li>
          介绍地址：
          <Link target="_blank" href="https://www.npc.ink/277510.html">Npcink</Link>
        </li>
        <li>
          开源地址：
          <Link target="_blank" href="https://github.com/npcink/npcink-site-toolbox">GitHub</Link>
        </li>
      </ul>
    </Paragraph>
    <Paragraph>
      早期给公司的子主题添加各项功能，管理不便，便独立出来，方便统一管理和维护；随着进一步的发展，功能增多，独乐乐不如众乐乐，于是免费分享出来，供大家使用。
    </Paragraph>
  </div>
);

const Proposal = () => (
  <div>
    <Paragraph>
      您可以通过以下方式，或通过下方联系方式，给出您的宝贵建议；我会酌情排期，实现有趣的功能。
    </Paragraph>
    <Paragraph>
      <ul style={{ paddingLeft: 20 }}>
        <li>
          <Link target="_blank" href="https://www.npc.ink/277510.html">文章评论</Link>
        </li>
        <li>
          <Link target="_blank" href="https://github.com/npcink/npcink-site-toolbox/issues">GitHub Issue</Link>
        </li>
      </ul>
    </Paragraph>
  </div>
);

const Links = () => (
  <div>
    <Paragraph>
      您可以通过以下方式联系到我：
    </Paragraph>
    <Paragraph>
      <ul style={{ paddingLeft: 20 }}>
        <li>
          <Link target="_blank" href="https://wpa.qq.com/msgrd?v=3&uin=1355471563">
            1355471563（QQ 好友）
          </Link>
        </li>
        <li>
          <Link target="_blank" href="mailto:1355471563@qq.com">
            1355471563@qq.com（邮件）
          </Link>
        </li>
      </ul>
    </Paragraph>
  </div>
);

const AboutAndSupport = () => (
  <>
    <SettingsSection title="关于插件">
      <AboutPlugin />
    </SettingsSection>
    <SettingsSection title="我有建议">
      <Proposal />
    </SettingsSection>
    <SettingsSection title="联系方式">
      <Links />
    </SettingsSection>
    <SettingsSection title="来源">
      <Source />
    </SettingsSection>
  </>
);

interface AboutProps {
  onNavigate?: (view: string, itemId?: string) => void;
}

const App: React.FC<AboutProps> = ({ onNavigate }) => {
  const tabs = [
    {
      key: "help",
      label: "使用帮助",
      content: <UsageHelp />,
    },
    {
      key: "runtime",
      label: "运行状态",
      content: <RuntimeStatus onNavigate={onNavigate} />,
    },
    {
      key: "ai-diagnostics",
      label: "AI 诊断",
      content: <AiDiagnostics />,
    },
    {
      key: "support",
      label: "关于与支持",
      content: <AboutAndSupport />,
    },
  ] as const;

  return (
    <SettingsTabs
      ariaLabel="关于与帮助分组"
      idPrefix="about-help"
      tabs={tabs}
    />
  );
};

export default App;
