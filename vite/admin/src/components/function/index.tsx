import React, { useState, useContext, useEffect } from "react";
import { Form, Input, Button, Space } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionSeo, FunctionAuxiliary } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { ModuleCard, DetailDrawer, ModuleRow } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";

const fromConfig = AntConfig.from;

type VerificationField = "baidu_tonji" | "google_tonji" | "biying_tonji";

const SiteInput = (props: any) => {
  const handleReset = () => {
    props.onChange("");
  };
  return (
    <div>
      <Space.Compact style={{ width: "100%" }}>
        <Input {...props} placeholder={__("自动处理代码")} />
        <Button onClick={handleReset}>{__("清空")}</Button>
      </Space.Compact>
    </div>
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
        title={__("简单 SEO")}
        description={__("基础 SEO 设置，推荐使用专业 SEO 插件替代")}
        featureId="function-seo-seo_single"
        tags={["SEO"]}
        switchable={false}
        actionLabel={__("配置")}
        onAction={() => setDrawerOpen(true)}
        aliases={["function-seo-seo_home", "function-seo-seo_category", "function-seo-title", "function-seo-keywords", "function-seo-description"]}
      />
      <DetailDrawer
        title={__("SEO 配置")}
        visible={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        description={__("简单 SEO 设置，仅解决有无问题")}
      >
        <Form
          labelCol={fromConfig.labelCol}
          wrapperCol={fromConfig.wrapperCol}
          style={{ maxWidth: fromConfig.maxWidth }}
          initialValues={publicData}
          onValuesChange={onValuesChange}
        >
          <ModuleRow
            title={__("文章 SEO")}
            featureId="function-seo-seo_single"
            enabled={!!formData.seo_single}
            onChange={(checked: boolean) => onValuesChange({ seo_single: checked })}
          />
          <Form.Item label={__("标题")} name="title" extra={__("站点标题")}>
            <Input />
          </Form.Item>
          <Form.Item label={__("关键词")} name="keywords" extra={__("用英文逗号分隔，建议不超过6个词")}>
            <Input />
          </Form.Item>
          <Form.Item label={__("描述")} name="description" extra={__("建议240字以内")}>
            <Input.TextArea rows={4} />
          </Form.Item>
          <ModuleRow
            title={__("分类和标签 SEO")}
            description={__("分类名称作标题、分类关键词和描述作 Meta，标签描述作 Meta")}
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
  const [verificationErrors, setVerificationErrors] = useState<Partial<Record<VerificationField, string>>>({});
  const drawerOpen = extDrawerOpen ?? intDrawerOpen;
  const setDrawerOpen = onDrawerOpenChange ?? setIntDrawerOpen;

  const onValuesChange = (changedValues: Partial<FunctionAuxiliary>, _allValues?: FunctionAuxiliary) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => { updateOption("function", "auxiliary", formData); }, [formData]);

  const setVerificationError = (field: VerificationField, message?: string) => {
    setVerificationErrors((current) => {
      if (message) return { ...current, [field]: message };
      const next = { ...current };
      delete next[field];
      return next;
    });
  };

  const getRawValue = (input: string | { target?: { value?: string } }) => (
    typeof input === "string" ? input : input?.target?.value || ""
  );

  const handleValueChange = (input: string | { target?: { value?: string } }) => {
    const value = getRawValue(input);
    if (!value) {
      setVerificationError("baidu_tonji");
      return "";
    }
    const regex = /hm\.js\?([A-Za-z0-9]+)/;
    const match = value.match(regex);
    if (match) {
      setVerificationError("baidu_tonji");
      return match[1];
    }
    setVerificationError("baidu_tonji", __("未识别统计 ID，请粘贴百度统计平台提供的完整代码。"));
    return "";
  };

  const extract_google = (input: string | { target?: { value?: string } }) => {
    const value = getRawValue(input);
    if (!value) {
      setVerificationError("google_tonji");
      return "";
    }
    const regex = /<meta\s+.*?name="google-site-verification".*?content="([A-Za-z0-9_-]+)".*?>/i;
    const match = value.match(regex);
    if (match) {
      setVerificationError("google_tonji");
      return match[1];
    }
    setVerificationError("google_tonji", __("未识别验证码，请粘贴 Google Search Console 提供的完整 HTML 标记。"));
    return "";
  };

  const extract_biying = (input: string | { target?: { value?: string } }) => {
    const value = getRawValue(input);
    if (!value) {
      setVerificationError("biying_tonji");
      return "";
    }
    const regex = /<meta\s+.*?name="msvalidate\.01".*?content="([A-Za-z0-9]+)".*?>/i;
    const match = value.match(regex);
    if (match) {
      setVerificationError("biying_tonji");
      return match[1];
    }
    setVerificationError("biying_tonji", __("未识别验证码，请粘贴 Bing Webmaster Tools 提供的完整 HTML Meta 标记。"));
    return "";
  };

  return (
    <>
      <ModuleCard
        title={__("辅助功能")}
        description={__("文章统计、恶意搜索屏蔽、站点验证")}
        featureId="function-auxiliary-single_count"
        switchable={false}
        actionLabel={__("配置")}
        onAction={() => setDrawerOpen(true)}
        aliases={["function-auxiliary-no_malice_key", "function-auxiliary-baidu_tonji", "function-auxiliary-google_tonji", "function-auxiliary-biying_tonji"]}
      />
      <DetailDrawer
        title={__("辅助功能配置")}
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
            title={__("文章访问统计")}
            featureId="function-auxiliary-single_count"
            enabled={!!formData.single_count}
            onChange={(checked: boolean) => onValuesChange({ single_count: checked })}
          />
          <ModuleRow
            title={__("屏蔽恶意关键词搜索")}
            featureId="function-auxiliary-no_malice_key"
            enabled={!!formData.no_malice_key}
            onChange={(checked: boolean) => onValuesChange({ no_malice_key: checked })}
          />
          <Form.Item label={__("恶意关键词")} name="malice_keu_content" extra={__("一行一个")}>
            <Input.TextArea rows={4} placeholder={__("一行一个")} />
          </Form.Item>
          <Form.Item
            label={__("百度统计")}
            name="baidu_tonji"
            getValueFromEvent={handleValueChange}
            validateStatus={verificationErrors.baidu_tonji ? "error" : undefined}
            help={verificationErrors.baidu_tonji}
            extra={<a href="https://tongji.baidu.com/main/setting/self/home/site/index" target="_blank" rel="noreferrer">获取代码</a>}
          >
            <SiteInput />
          </Form.Item>
          <Form.Item
            label={__("Google 站点验证")}
            name="google_tonji"
            getValueFromEvent={extract_google}
            validateStatus={verificationErrors.google_tonji ? "error" : undefined}
            help={verificationErrors.google_tonji}
            extra={<a href="https://search.google.com/search-console/about" target="_blank" rel="noreferrer">获取标记</a>}
          >
            <SiteInput />
          </Form.Item>
          <Form.Item
            label={__("Bing 站点验证")}
            name="biying_tonji"
            getValueFromEvent={extract_biying}
            validateStatus={verificationErrors.biying_tonji ? "error" : undefined}
            help={verificationErrors.biying_tonji}
            extra={<a href="https://www.bing.com/webmasters" target="_blank" rel="noreferrer">获取标记</a>}
          >
            <SiteInput />
          </Form.Item>
        </Form>
      </DetailDrawer>
    </>
  );
};

const App: React.FC<{ targetItemId?: string }> = ({ targetItemId }) => {
  const [seoDrawerOpen, setSeoDrawerOpen] = useState(false);
  const [auxiliaryDrawerOpen, setAuxiliaryDrawerOpen] = useState(false);

  useEffect(() => {
    if (!targetItemId) return;
    if (targetItemId.startsWith("function-seo-")) setSeoDrawerOpen(true);
    else if (targetItemId.startsWith("function-auxiliary-")) setAuxiliaryDrawerOpen(true);
  }, [targetItemId]);

  return (
    <div className="mabox-module-grid">
      <SeoCard drawerOpen={seoDrawerOpen} onDrawerOpenChange={setSeoDrawerOpen} />
      <AuxiliaryCard drawerOpen={auxiliaryDrawerOpen} onDrawerOpenChange={setAuxiliaryDrawerOpen} />
    </div>
  );
};

export default App;
