import React, { useState, useContext, useEffect } from "react";
import { Form, Input, Button, Space, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionTips, FunctionSeo, FunctionAuxiliary } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import TimePeriod from "@/basic/timeInput";
import TextAreaHtml from "@/basic/htmlInput";
import { ModuleCard, DetailDrawer, ModuleRow } from "@/components/settings-ui";

const fromConfig = AntConfig.from;

const SiteInput = (props: any) => {
  const handleReset = () => {
    props.onChange("");
  };
  return (
    <div>
      <Space.Compact style={{ width: "100%" }}>
        <Input {...props} placeholder="自动处理代码" />
        <Button onClick={handleReset}>清空</Button>
      </Space.Compact>
    </div>
  );
};

const TipsCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.function?.config || defaultVarOption.function.config;
  const [formData, setFormData] = useState(publicData);
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: Partial<FunctionTips>) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("function", "config", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title="提示条"
        description="在页面顶部或底部显示提示信息"
        featureId="function-tips-pop_tips"
        tags={["推荐"]}
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["function-tips-tips_content", "function-tips-tips_button", "function-tips-tips_link", "function-tips-tips_time"]}
      />
      <DetailDrawer
        title="提示条配置"
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        description="配置弹窗提示的显示内容和时间"
      >
        <Form
          labelCol={fromConfig.labelCol}
          wrapperCol={fromConfig.wrapperCol}
          style={{ maxWidth: fromConfig.maxWidth }}
          initialValues={publicData}
          onValuesChange={onValuesChange}
        >
          <ModuleRow
            title="启用提示条"
            featureId="function-tips-pop_tips"
            enabled={!!formData.pop_tips}
            onChange={(checked: boolean) => onValuesChange({ pop_tips: checked })}
          />
          <Form.Item label="提示内容" name="tips_content" extra="支持HTML">
            <TextAreaHtml />
          </Form.Item>
          <Form.Item label="按钮文字" name="tips_button">
            <Input />
          </Form.Item>
          <Form.Item label="按钮链接" name="tips_link">
            <Input />
          </Form.Item>
          <Form.Item label="显示时间" name="tips_time">
            <TimePeriod />
          </Form.Item>
        </Form>
      </DetailDrawer>
    </>
  );
};

const SeoCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.function?.seo || defaultVarOption.function.seo;
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: Partial<FunctionSeo>, _allValues?: FunctionSeo) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("function", "seo", formData); }, [formData]);

  return (
    <>
      <ModuleCard
        title="简单 SEO"
        description="基础 SEO 设置，推荐使用专业 SEO 插件替代"
        featureId="function-seo-seo_single"
        tags={["SEO"]}
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["function-seo-seo_home", "function-seo-seo_category", "function-seo-title", "function-seo-keywords", "function-seo-description"]}
      />
      <DetailDrawer
        title="SEO 配置"
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        description="简单 SEO 设置，仅解决有无问题"
      >
        <Form
          labelCol={fromConfig.labelCol}
          wrapperCol={fromConfig.wrapperCol}
          style={{ maxWidth: fromConfig.maxWidth }}
          initialValues={publicData}
          onValuesChange={onValuesChange}
        >
          <ModuleRow
            title="文章 SEO"
            featureId="function-seo-seo_single"
            enabled={!!formData.seo_single}
            onChange={(checked: boolean) => onValuesChange({ seo_single: checked })}
          />
          <Form.Item label="标题" name="title" extra="站点标题">
            <Input />
          </Form.Item>
          <Form.Item label="关键词" name="keywords" extra="用英文逗号分隔，建议不超过6个词">
            <Input />
          </Form.Item>
          <Form.Item label="描述" name="description" extra="建议240字以内">
            <Input.TextArea rows={4} />
          </Form.Item>
          <ModuleRow
            title="分类和标签 SEO"
            description="分类名称作标题、分类关键词和描述作 Meta，标签描述作 Meta"
            featureId="function-seo-seo_category"
            enabled={!!formData.seo_category}
            onChange={(checked: boolean) => onValuesChange({ seo_category: checked })}
          />
        </Form>
      </DetailDrawer>
    </>
  );
};

const AuxiliaryCard: React.FC<{ drawerOpen?: boolean; onDrawerOpenChange?: (open: boolean) => void }> = ({ drawerOpen: extDrawerOpen, onDrawerOpenChange }) => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.function?.auxiliary || defaultVarOption.function.auxiliary;
  const [formData, setFormData] = useState(publicData || {});
  const [intDrawerOpen, setIntDrawerOpen] = useState(false);
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: Partial<FunctionAuxiliary>, _allValues?: FunctionAuxiliary) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("function", "auxiliary", formData); }, [formData]);

  const handleValueChange = (e: { target: { value: any } }) => {
    if (!e) return;
    const value = e.target.value;
    const regex = /hm\.js\?([A-Za-z0-9]+)/;
    const match = value.match(regex);
    if (match) return match[1];
    message.error("处理失败，请输入百度统计平台的完整统计代码");
    return "";
  };

  const extract_google = (e: { target: { value: any } }) => {
    if (!e) return;
    const value = e.target.value;
    const regex = /<meta\s+.*?name="google-site-verification".*?content="([A-Za-z0-9_-]+)".*?>/i;
    const match = value.match(regex);
    if (match) return match[1];
    message.error("处理失败，请输入谷歌平台完整 HTML 标记");
    return "";
  };

  const extract_biying = (e: { target: { value: any } }) => {
    if (!e) return;
    const value = e.target.value;
    const regex = /<meta\s+.*?name="msvalidate\.01".*?content="([A-Za-z0-9]+)".*?>/i;
    const match = value.match(regex);
    if (match) return match[1];
    message.error("处理失败，请输入必应平台完整 HTML Meta 标记");
    return "";
  };

  return (
    <>
      <ModuleCard
        title="辅助功能"
        description="文章统计、恶意搜索屏蔽、站点验证"
        featureId="function-auxiliary-single_count"
        switchable={false}
        actionLabel="配置"
        onAction={() => setDrawerOpen(true)}
        aliases={["function-auxiliary-no_malice_key", "function-auxiliary-baidu_tonji", "function-auxiliary-google_tonji", "function-auxiliary-biying_tonji"]}
      />
      <DetailDrawer
        title="辅助功能配置"
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
      >
        <Form
          labelCol={fromConfig.labelCol}
          wrapperCol={fromConfig.wrapperCol}
          style={{ maxWidth: fromConfig.maxWidth }}
          initialValues={publicData}
          onValuesChange={onValuesChange}
        >
          <ModuleRow
            title="文章访问统计"
            featureId="function-auxiliary-single_count"
            enabled={!!formData.single_count}
            onChange={(checked: boolean) => onValuesChange({ single_count: checked })}
          />
          <ModuleRow
            title="屏蔽恶意关键词搜索"
            featureId="function-auxiliary-no_malice_key"
            enabled={!!formData.no_malice_key}
            onChange={(checked: boolean) => onValuesChange({ no_malice_key: checked })}
          />
          <Form.Item label="恶意关键词" name="malice_keu_content" extra="一行一个">
            <Input.TextArea rows={4} placeholder="一行一个" />
          </Form.Item>
          <Form.Item label="百度统计" name="baidu_tonji" getValueFromEvent={handleValueChange} extra={<a href="https://tongji.baidu.com/main/setting/self/home/site/index" target="_blank">获取代码</a>}>
            <SiteInput />
          </Form.Item>
          <Form.Item label="谷歌统计" name="google_tonji" getValueFromEvent={extract_google} extra={<a href="https://search.google.com/search-console/about" target="_blank">获取标记</a>}>
            <SiteInput />
          </Form.Item>
          <Form.Item label="必应统计" name="biying_tonji" getValueFromEvent={extract_biying} extra={<a href="https://www.bing.com/webmasters" target="_blank">获取标记</a>}>
            <SiteInput />
          </Form.Item>
        </Form>
      </DetailDrawer>
    </>
  );
};

const App: React.FC<{ targetItemId?: string }> = ({ targetItemId }) => {
  const [tipsDrawerOpen, setTipsDrawerOpen] = useState(false);
  const [seoDrawerOpen, setSeoDrawerOpen] = useState(false);
  const [auxiliaryDrawerOpen, setAuxiliaryDrawerOpen] = useState(false);

  useEffect(() => {
    if (!targetItemId) return;
    if (targetItemId.startsWith("function-tips-")) setTipsDrawerOpen(true);
    else if (targetItemId.startsWith("function-seo-")) setSeoDrawerOpen(true);
    else if (targetItemId.startsWith("function-auxiliary-")) setAuxiliaryDrawerOpen(true);
  }, [targetItemId]);

  return (
    <div className="mabox-module-grid">
      <TipsCard drawerOpen={tipsDrawerOpen} onDrawerOpenChange={setTipsDrawerOpen} />
      <SeoCard drawerOpen={seoDrawerOpen} onDrawerOpenChange={setSeoDrawerOpen} />
      <AuxiliaryCard drawerOpen={auxiliaryDrawerOpen} onDrawerOpenChange={setAuxiliaryDrawerOpen} />
    </div>
  );
};

export default App;
