//页面 - 功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import TimePeriod from "@/basic/timeInput";
import TextAreaHtml from "@/basic/htmlInput";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFunction } from "@/tool/interface";
import SelectImage from "@/basic/selectImage";
import FixedImage from "@/basic/fixedImage";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = PageFunction;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData = optionData.page?.function || defaultVarOption.page.function;

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
    updateOption("page", "function", formData);
  }, [formData]);

  return (
    <SettingsSection title="功能">
      <Form
        name="function"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="首图作特色图"
          description="初次发布文章，未设置特色图时，自动将第一张图设为特色图"
          featureId="page-function-first_picture"
          enabled={formData.first_picture as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ first_picture: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title="文章内关键词添加内链"
          description="文章内的内容与添加的标签相同，则添加对应标签的链接"
          featureId="page-function-add_inks"
          enabled={formData.add_inks as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ add_inks: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/15286.html?=magick-mami", "_blank")}
        />
        <ModuleRow
          title="未登录模糊文章内图片"
          featureId="page-function-no_login_img"
          enabled={formData.no_login_img as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ no_login_img: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title="添加最后更新时间"
          description="文章末尾添加最后更新时间，文章发布24小时后再次修改，即可展示"
          featureId="page-function-add_last_update"
          enabled={formData.add_last_update as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ add_last_update: checked } as Partial<FieldType>, formData);
          }}
        />
        <Form.Item<FieldType>
          label="维护提示"
          name="maintenance_tips"
          extra={
            <>
              进行可能影响前端页面的配置时，可临时关闭前端页面，避免影响用户体验。（管理员不影响）
            </>
          }
        >
          <FixedImage alists={serviceList} />
        </Form.Item>
        {formData.maintenance_tips !== "false" && (
          <>
            <Form.Item
              label="倒计时"
              name="countdown"
              extra={<>此时间段内才会显示内容</>}
            >
              <TimePeriod />
            </Form.Item>
            <Form.Item label="倒计时标题" name="countdown_title">
              <Input />
            </Form.Item>
            <Form.Item
              label="倒计时图片"
              name="countdown_image"
              extra={
                <>
                  不同模版位置不一样，请手动确认效果，，全屏显示时，推荐使用1920×1080像素的图片
                </>
              }
            >
              <SelectImage />
            </Form.Item>
            <Form.Item
              label="倒计时内容"
              name="countdown_content"
              extra={
                <>
                  可使用HTML，例如：
                  <br />
                  <pre className="mabox-preformatted-hint">
                    &lt;p&gt; 抱歉，我们的网站正在维护中...
                    <br />
                    &lt;h5 class="dull-text"&gt; <br />
                    请倒计时结束后再回来，我们准备了全新的内容哦！
                    <br />
                    &lt;/h5&gt;
                    <br />
                    &lt;/p&gt;
                  </pre>
                </>
              }
            >
              <TextAreaHtml />
            </Form.Item>
          </>
        )}
      </Form>
    </SettingsSection>
  );
};

//准备维护界面
import Default from "@/assets/page/function/service/默认简洁.png";
import Default_img from "@/assets/page/function/service/默认带图.png";
import Red from "@/assets/page/function/service/红色纯粹.png";
const serviceList = [
  { value: "default", label: Default, title: "默认简洁" },
  { value: "default_img", label: Default_img, title: "默认带图" },
  { value: "red", label: Red, title: "红色纯粹" },
];

export default App;
