//站点 - 模版
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeSite } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

//选项类型
type FieldType = OptimizeSite;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData = optionData.optimize?.site || defaultVarOption.optimize.site;

  //创建变量并设默认值
  const [formData, setFormData] = useState(publicData || {});

  //表单同步修改值
  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("optimize", "site", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="site"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={publicData}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={() => {}}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>站点</h2>
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-site-hide_top_toolbar"
          label="隐藏顶部工具条"
          name="hide_top_toolbar"
          valuePropName="checked"
          extra={"WordPress、主题和插件不再提示更新"}
        >
          <FeatureSwitch featureId="optimize-site-hide_top_toolbar" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-site-renew"
          label="禁用自动更新"
          name="renew"
          valuePropName="checked"
          extra={"WordPress、主题和插件不再提示更新"}
        >
          <FeatureSwitch featureId="optimize-site-renew" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-site-remove_RSS_version"
          label="移除版本信息"
          name="remove_RSS_version"
          valuePropName="checked"
          extra={
            "从RSS源和网站中删除WordPress版本信息，如果您无法保持您的WordPres版本为最新，推荐开启"
          }
        >
          <FeatureSwitch featureId="optimize-site-remove_RSS_version" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-site-no_escape"
          label={'禁止title中的 "-" 被转义'}
          name="no_escape"
          valuePropName="checked"
          extra={"让网页标题符号正常显示"}
        >
          <FeatureSwitch featureId="optimize-site-no_escape" />
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-site-category_link_simplify"
          label="分类链接简化"
          name="category_link_simplify"
          valuePropName="checked"
          extra={"去掉分类目录链接中的 category 字符。"}
        >
          <FeatureSwitch featureId="optimize-site-category_link_simplify" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-site-search_link_simplify"
          label="搜索链接优化"
          name="search_link_simplify"
          valuePropName="checked"
          extra={
            <>
              <code>?s=关键词</code>改为<code>域名/search/关键词</code>
            </>
          }
        >
          <FeatureSwitch featureId="optimize-site-search_link_simplify" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-site-remove_sitemap_users"
          label="安全 - 移除 wp-sitemap-users"
          name="remove_sitemap_users"
          valuePropName="checked"
          extra={"移除原生站点地图中的用户信息部分，可减少用户信息暴露风险"}
        >
          <FeatureSwitch featureId="optimize-site-remove_sitemap_users" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-site-user_list_show_nickname"
          label="用户列表展示昵称"
          name="user_list_show_nickname"
          valuePropName="checked"
          extra={"移除原生站点地图中的用户信息部分，可减少用户信息暴露风险"}
        >
          <FeatureSwitch featureId="optimize-site-user_list_show_nickname" />
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-site-cdn_replace"
          label="国内 CDN 替换"
          name="cdn_replace"
          valuePropName="checked"
          extra={"将 WordPress 加载的国外资源替换为国内 CDN 镜像，提升国内访问速度"}
        >
          <FeatureSwitch featureId="optimize-site-cdn_replace" />
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-site-cdn_gravatar"
          label="Gravatar 头像替换"
          name="cdn_gravatar"
          valuePropName="checked"
          extra={"将 gravatar.com 替换为国内镜像，解决头像无法加载的问题"}
        >
          <FeatureSwitch featureId="optimize-site-cdn_gravatar" />
        </Form.Item>

        <Form.Item<FieldType>
          label="Gravatar 镜像地址"
          name="cdn_gravatar_mirror"
          extra={"默认: gravatar.loli.net/avatar/"}
        >
          <Input placeholder="gravatar.loli.net/avatar/" />
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-site-cdn_google_fonts"
          label="Google Fonts 替换"
          name="cdn_google_fonts"
          valuePropName="checked"
          extra={"将 fonts.googleapis.com 替换为国内镜像"}
        >
          <FeatureSwitch featureId="optimize-site-cdn_google_fonts" />
        </Form.Item>

        <Form.Item<FieldType>
          label="Google Fonts 镜像地址"
          name="cdn_google_fonts_mirror"
          extra={"默认: fonts.loli.net"}
        >
          <Input placeholder="fonts.loli.net" />
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-site-cdn_google_ajax"
          label="Google Ajax 替换"
          name="cdn_google_ajax"
          valuePropName="checked"
          extra={"将 ajax.googleapis.com 替换为 ajax.loli.net"}
        >
          <FeatureSwitch featureId="optimize-site-cdn_google_ajax" />
        </Form.Item>

        <Form.Item<FieldType>
          label="自定义 CDN 替换"
          name="cdn_custom"
          extra={"每行一条规则，格式: 原地址 => 新地址，支持 style_loader_src 和 script_loader_src"}
        >
          <Input.TextArea rows={4} placeholder={"example.com/cdn/ => cdn.example.com/"} />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
