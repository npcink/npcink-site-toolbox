import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, Button, Space, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionAuxiliary } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = FunctionAuxiliary;

const fromConfig = AntConfig.from;

const { TextArea } = Input;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData =
    optionData.function?.auxiliary || defaultVarOption.function.auxiliary;

  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: Partial<FieldType>, _allValues?: FieldType) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  useEffect(() => {
    updateOption("function", "auxiliary", formData);
  }, [formData]);

  const handleValueChange = (e: { target: { value: any } }) => {
    if (!e) {
      return;
    }
    const value = e.target.value;
    const regex = /hm\.js\?([A-Za-z0-9]+)/;
    const match = value.match(regex);

    if (match) {
      return match[1];
    } else {
      message.error("处理失败，请输入百度统计平台的完整统计代码");
      return "";
    }
  };

  const extract_google = (e: { target: { value: any } }) => {
    if (!e) {
      return;
    }
    const value = e.target.value;
    const regex =
      /<meta\s+.*?name="google-site-verification".*?content="([A-Za-z0-9_-]+)".*?>/i;

    const match = value.match(regex);
    if (match) {
      return match[1];
    } else {
      message.error("处理失败，请输入谷歌平台完整 HTML 标记");
      return "";
    }
  };

  const extract_biying = (e: { target: { value: any } }) => {
    if (!e) {
      return;
    }
    const value = e.target.value;

    const regex =
      /<meta\s+.*?name="msvalidate\.01".*?content="([A-Za-z0-9]+)".*?>/i;
    const match = value.match(regex);
    if (match) {
      return match[1];
    } else {
      message.error("处理失败，请输入必应平台完整 HTML Meta 标记");
      return "";
    }
  };

  return (
    <SettingsSection title="辅助功能">
      <Form
        name="auxiliary"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={formData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="文章统计"
          description="开启后显示在仪表盘下"
          featureId="function-auxiliary-single_count"
          enabled={formData.single_count as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ single_count: checked });
          }}
        />

        <ModuleRow
          title="屏蔽恶意关键词搜索"
          description="禁止搜索指定词汇"
          featureId="function-auxiliary-no_malice_key"
          enabled={formData.no_malice_key as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ no_malice_key: checked });
          }}
        >
          <Form.Item<FieldType>
            label="输入关键词"
            name="malice_keu_content"
            extra={'输入您的关键词，以"回车键"分隔，一行一个'}
          >
            <TextArea rows={4} placeholder="一行一个" />
          </Form.Item>
        </ModuleRow>

        <Form.Item<FieldType>
          label="百度统计"
          name="baidu_tonji"
          getValueFromEvent={handleValueChange}
          extra={
            <p>
              <a
                href="https://tongji.baidu.com/main/setting/self/home/site/index"
                target="_blank"
              >
                百度统计
              </a>
              → 代码管理（左侧） → 代码获取 → 获取代码 →
              复制代码贴入输入框中并保存即可
            </p>
          }
        >
          <SiteInput />
        </Form.Item>
        <Form.Item<FieldType>
          label="谷歌统计"
          name="google_tonji"
          getValueFromEvent={extract_google}
          extra={
            <span>
              <a
                href="https://search.google.com/search-console/about"
                target="_blank"
              >
                谷歌统计
              </a>
              ：
              <pre className="mabox-preformatted-hint">
                &lt;meta name="google-site-verification" content="HB..." /&gt;
              </pre>
            </span>
          }
        >
          <SiteInput />
        </Form.Item>
        <Form.Item<FieldType>
          label="必应统计"
          name="biying_tonji"
          getValueFromEvent={extract_biying}
          extra={
            <span>
              <a href="https://www.bing.com/webmasters" target="_blank">
                必应统计
              </a>
              ：
              <pre className="mabox-preformatted-hint">
                &lt;meta name="msvalidate.01" content="CF..." /&gt;
              </pre>
            </span>
          }
        >
          <SiteInput />
        </Form.Item>
      </Form>
    </SettingsSection>
  );
};

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

export default App;
