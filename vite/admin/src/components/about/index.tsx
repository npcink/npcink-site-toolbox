import { Typography } from "antd";
import { SettingsSection } from "@/components/settings-ui";
import Source from "@/components/about/table";

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
          <Link target="_blank" href="https://gitee.com/gitgreat/wp-magick-toolbox">Gitee</Link>
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
          <Link target="_blank" href="https://gitee.com/gitgreat/wp-magick-toolbox/issues">Gitee Issue</Link>
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

const App: React.FC = () => {
  return (
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
};

export default App;