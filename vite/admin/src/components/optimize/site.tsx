import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeSite } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = OptimizeSite;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData = optionData.optimize?.site || defaultVarOption.optimize.site;

  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  useEffect(() => {
    updateOption("optimize", "site", formData);
  }, [formData]);

  return (
    <SettingsSection title="站点" description="站点基础优化设置">
      <Form
        name="site"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="隐藏顶部工具条"
          description="对无法编辑文章的登录用户隐藏前台顶部工具条"
          featureId="optimize-site-hide_top_toolbar"
          enabled={formData.hide_top_toolbar as boolean}
          onChange={(checked: boolean) => onValuesChange({ hide_top_toolbar: checked } as Partial<FieldType>, formData)}
        />
        <ModuleRow
          title="移除版本信息"
          description="从RSS源和网站中删除WordPress版本信息，如果您无法保持您的WordPres版本为最新，推荐开启"
          featureId="optimize-site-remove_RSS_version"
          enabled={formData.remove_RSS_version as boolean}
          onChange={(checked: boolean) => onValuesChange({ remove_RSS_version: checked } as Partial<FieldType>, formData)}
          tags={["安全"]}
        />
        <ModuleRow
          title={'禁止title中的 "-" 被转义'}
          description="让网页标题符号正常显示"
          featureId="optimize-site-no_escape"
          enabled={formData.no_escape as boolean}
          onChange={(checked: boolean) => onValuesChange({ no_escape: checked } as Partial<FieldType>, formData)}
        />
        <ModuleRow
          title="分类链接简化"
          description="去掉分类目录链接中的 category 字符，修改后旧链接可能失效"
          featureId="optimize-site-category_link_simplify"
          enabled={formData.category_link_simplify as boolean}
          onChange={(checked: boolean) => onValuesChange({ category_link_simplify: checked } as Partial<FieldType>, formData)}
          tags={["谨慎"]}
        />
        <ModuleRow
          title="搜索链接优化"
          description='将 ?s=关键词 改为 域名/search/关键词，部分主题可能不兼容'
          featureId="optimize-site-search_link_simplify"
          enabled={formData.search_link_simplify as boolean}
          onChange={(checked: boolean) => onValuesChange({ search_link_simplify: checked } as Partial<FieldType>, formData)}
          tags={["SEO"]}
        />
        <ModuleRow
          title="安全 - 移除 wp-sitemap-users"
          description="移除原生站点地图中的用户信息部分，可减少用户信息暴露风险"
          featureId="optimize-site-remove_sitemap_users"
          enabled={formData.remove_sitemap_users as boolean}
          onChange={(checked: boolean) => onValuesChange({ remove_sitemap_users: checked } as Partial<FieldType>, formData)}
          tags={["安全"]}
        />
        <ModuleRow
          title="用户列表展示昵称"
          description="在用户列表中以“昵称”列替代“姓名”列"
          featureId="optimize-site-user_list_show_nickname"
          enabled={formData.user_list_show_nickname as boolean}
          onChange={(checked: boolean) => onValuesChange({ user_list_show_nickname: checked } as Partial<FieldType>, formData)}
        />
        <ModuleRow
          title="国内 CDN 替换"
          description="将 WordPress 加载的国外资源替换为国内 CDN 镜像，提升国内访问速度"
          featureId="optimize-site-cdn_replace"
          enabled={formData.cdn_replace as boolean}
          onChange={(checked: boolean) => onValuesChange({ cdn_replace: checked } as Partial<FieldType>, formData)}
          tags={["性能"]}
        />
        {formData.cdn_replace && (
          <>
            <ModuleRow
              title="Gravatar 头像替换"
              description="将 gravatar.com 替换为国内镜像，解决头像无法加载的问题"
              featureId="optimize-site-cdn_gravatar"
              enabled={formData.cdn_gravatar as boolean}
              onChange={(checked: boolean) => onValuesChange({ cdn_gravatar: checked } as Partial<FieldType>, formData)}
              tags={["性能"]}
            />

            {formData.cdn_gravatar && (
              <Form.Item<FieldType>
                label="Gravatar 镜像地址"
                name="cdn_gravatar_mirror"
                extra={"默认: gravatar.loli.net/avatar/"}
              >
                <Input placeholder="gravatar.loli.net/avatar/" />
              </Form.Item>
            )}

            <ModuleRow
              title="Google Fonts 替换"
              description="将 fonts.googleapis.com 替换为国内镜像，需确认镜像站可用性"
              featureId="optimize-site-cdn_google_fonts"
              enabled={formData.cdn_google_fonts as boolean}
              onChange={(checked: boolean) => onValuesChange({ cdn_google_fonts: checked } as Partial<FieldType>, formData)}
              tags={["性能"]}
            />

            {formData.cdn_google_fonts && (
              <Form.Item<FieldType>
                label="Google Fonts 镜像地址"
                name="cdn_google_fonts_mirror"
                extra={"默认: fonts.loli.net"}
              >
                <Input placeholder="fonts.loli.net" />
              </Form.Item>
            )}

            <ModuleRow
              title="Google Ajax 替换"
              description="将 ajax.googleapis.com 替换为 ajax.loli.net"
              featureId="optimize-site-cdn_google_ajax"
              enabled={formData.cdn_google_ajax as boolean}
              onChange={(checked: boolean) => onValuesChange({ cdn_google_ajax: checked } as Partial<FieldType>, formData)}
            />

            <Form.Item<FieldType>
              label="自定义 CDN 替换"
              name="cdn_custom"
              extra={"每行一条规则，格式: 原地址 => 新地址，支持 style_loader_src 和 script_loader_src"}
            >
              <Input.TextArea rows={4} placeholder={"example.com/cdn/ => cdn.example.com/"} />
            </Form.Item>
          </>
        )}

        <ModuleRow
          title="隐藏邮件中的 IP"
          description="在 WordPress 发送的邮件中隐藏 IP 地址，保护用户隐私"
          featureId="optimize-site-hide_email_ip"
          enabled={formData.hide_email_ip as boolean}
          onChange={(checked: boolean) => onValuesChange({ hide_email_ip: checked } as Partial<FieldType>, formData)}
          tags={["安全"]}
        />
      </Form>
    </SettingsSection>
  );
};

export default App;
