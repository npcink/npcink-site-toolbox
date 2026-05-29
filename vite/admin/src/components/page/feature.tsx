//页面 - 外观优化
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, InputNumber } from "antd";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFeature } from "@/tool/interface";
import FixedImage from "@/basic/fixedImage";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = PageFeature;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.page?.feature || defaultVarOption.page.feature;
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
    updateOption("page", "feature", formData);
  }, [formData]);

  return (
    <SettingsSection title="外观">
      <Form
        name="aspect"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <h3 className="menu-header">特效</h3>

        <ModuleRow
          title="顶部加载进度条"
          description="火狐浏览器不显示"
          featureId="page-feature-top_loading"
          enabled={formData.top_loading as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ top_loading: checked } as Partial<FieldType>, formData);
          }}
        />

        <ModuleRow
          title="阅读进度条"
          description="文章页面顶部显示阅读进度指示器，仅文章页展示"
          featureId="page-feature-reading_progress"
          enabled={formData.reading_progress as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ reading_progress: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item<FieldType> label="进度条颜色" name="reading_progress_color">
            <Input style={{ width: "30%" }} placeholder="#1677ff" />
          </Form.Item>
          <Form.Item<FieldType>
            label="进度条高度"
            name="reading_progress_height"
            extra={"单位: 像素"}
          >
            <InputNumber addonAfter={"px"} style={{ width: "120px" }} min={1} max={10} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title="字体切换"
          description="页面右下角添加字体切换按钮，支持多种字体切换"
          featureId="page-feature-font_switch"
          enabled={formData.font_switch as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ font_switch: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item<FieldType>
            label="字体列表"
            name="fonts"
            extra={"每行一个字体名称，用逗号分隔"}
          >
            <Input.TextArea rows={3} placeholder="Microsoft YaHei,Simsun,PingFang SC" />
          </Form.Item>
          <Form.Item<FieldType> label="按钮位置" name="font_position">
            <Input style={{ width: "200px" }} placeholder="bottom-right" />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title="全站变灰"
          description="特殊时间下让网站变灰，有特别的意义"
          featureId="page-feature-site_grey"
          enabled={formData.site_grey as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ site_grey: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/14874.html", "_blank")}
        />

        <ModuleRow
          title="平滑滚动"
          description="让页面滚动起来更丝滑，部分浏览器不支持"
          featureId="page-feature-page_scrolling"
          enabled={formData.page_scrolling as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ page_scrolling: checked } as Partial<FieldType>, formData);
          }}
        />


        <Form.Item<FieldType>
          id="page-feature-copy_pop_up"
          label="复制弹窗"
          name="copy_pop_up"
          extra={<>复制文本时进行弹窗提示</>}
        >
          <FixedImage alists={popUpList} />
        </Form.Item>


        <h3 className="menu-header">美化</h3>

        <Form.Item<FieldType>
          id="page-feature-scrol"
          label="美化 - 美化滚动条"
          name="scrol"
          extra={
            <>
              让你的页面滚动条更美观，
              <a href="https://www.npc.ink/6217.html" target="_blank">
                详情
              </a>
            </>
          }
        >
          <FixedImage alists={scrollBarList} />
        </Form.Item>

        <h3 className="menu-header">挂件</h3>


      </Form>
    </SettingsSection>
  );
};

//滚动条
import DiffuseBar from "@/assets/page/feature/scrollBar/默认.png";
import Color from "@/assets/page/feature/scrollBar/彩条.png";
const scrollBarList = [
  { value: "default", label: DiffuseBar, title: "默认" },
  { value: "color", label: Color, title: "彩条" },
];

//弹窗
import Concise from "@/assets/page/feature/popUp/原生弹窗.png";
import Sweetalert from "@/assets/page/feature/popUp/通用圆角.png";
const popUpList = [
  { value: "concise", label: Concise, title: "原生弹窗" },
  { value: "sweetalert", label: Sweetalert, title: "通用圆角" },
];

export default App;
