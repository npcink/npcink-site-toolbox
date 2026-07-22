import { Typography } from "antd";
import { SettingsSection, SettingsTabs } from "@/components/settings-ui";
import Source from "@/components/about/table";
import AiDiagnostics from "@/components/about/ai-diagnostics";
import RuntimeStatus from "@/components/about/runtime-status";

const { Paragraph, Link } = Typography;

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
          <Link target="_blank" href="https://github.com/muze-page/npcink-site-toolbox">GitHub</Link>
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
          <Link target="_blank" href="https://github.com/muze-page/npcink-site-toolbox/issues">GitHub Issue</Link>
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
          <Link target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=1355471563">
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
