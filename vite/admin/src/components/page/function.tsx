//页面 - 功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { InfoCircleOutlined } from "@ant-design/icons";
import { Button, Form, Input, Popover } from "antd";
import TimePeriod from "@/basic/timeInput";
import TextAreaHtml from "@/basic/htmlInput";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFunction } from "@/tool/interface";
import SelectImage from "@/basic/selectImage";
import FixedImage from "@/basic/fixedImage";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";
import "./function.css";

type FieldType = PageFunction;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const [form] = Form.useForm<FieldType>();

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

  const maintenanceEnabled = formData.maintenance_tips !== "false";

  const setMaintenanceEnabled = (checked: boolean) => {
    const maintenanceTips = checked ? "default" : "false";
    form.setFieldValue("maintenance_tips", maintenanceTips);
    onValuesChange(
      { maintenance_tips: maintenanceTips },
      { ...formData, maintenance_tips: maintenanceTips },
    );
  };

  return (
    <SettingsSection title={__("功能")}>
      <Form
        form={form}
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
          title={__("首图作特色图")}
          description={__("初次发布文章，未设置特色图时，自动将第一张图设为特色图")}
          featureId="page-function-first_picture"
          enabled={formData.first_picture as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ first_picture: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title={__("文章内关键词添加内链")}
          description={__("文章内的内容与添加的标签相同，则添加对应标签的链接")}
          featureId="page-function-add_inks"
          enabled={formData.add_inks as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ add_inks: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/15286.html?=magick-mami", "_blank")}
        />
        <ModuleRow
          title={__("未登录模糊文章内图片")}
          featureId="page-function-no_login_img"
          enabled={formData.no_login_img as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ no_login_img: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title={__("添加最后更新时间")}
          description={__("文章末尾添加最后更新时间，文章发布24小时后再次修改，即可展示")}
          featureId="page-function-add_last_update"
          enabled={formData.add_last_update as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ add_last_update: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title={__("维护提示页")}
          description={__("临时关闭前台访问，管理员仍可正常访问")}
          featureId="page-function-maintenance_tips"
          enabled={maintenanceEnabled}
          onChange={setMaintenanceEnabled}
          tags={["谨慎"]}
        >
          <div className="mabox-maintenance-config">
            <section className="mabox-maintenance-section" aria-labelledby="mabox-maintenance-style-title">
              <div className="mabox-maintenance-section-heading">
                <h3 id="mabox-maintenance-style-title">{__("页面样式")}</h3>
                <p>{__("选择访客看到的维护提示样式。")}</p>
              </div>
              <Form.Item<FieldType> name="maintenance_tips" noStyle>
                <FixedImage alists={serviceList} includeDisabled={false} />
              </Form.Item>
            </section>

            <section className="mabox-maintenance-section" aria-labelledby="mabox-maintenance-schedule-title">
              <div className="mabox-maintenance-section-heading">
                <h3 id="mabox-maintenance-schedule-title">{__("显示计划")}</h3>
                <p>{__("可选；留空时启用后持续显示维护提示。")}</p>
              </div>
              <div className="mabox-maintenance-field">
                <span className="mabox-maintenance-field-label">{__("显示时间（可选）")}</span>
                <Form.Item name="countdown" noStyle>
                  <TimePeriod
                    aria-label={__("维护提示显示时间")}
                    aria-describedby="mabox-maintenance-schedule-help"
                    className="mabox-maintenance-time-range"
                  />
                </Form.Item>
                <p id="mabox-maintenance-schedule-help" className="mabox-maintenance-field-help">
                  {__("只有在所选时间段内，访客才会看到维护提示。")}
                </p>
              </div>
            </section>

            <section className="mabox-maintenance-section" aria-labelledby="mabox-maintenance-content-title">
              <div className="mabox-maintenance-section-heading">
                <h3 id="mabox-maintenance-content-title">{__("维护内容")}</h3>
                <p>{__("填写提示标题、可选背景图片和详细说明。")}</p>
              </div>
              <div className="mabox-maintenance-fields">
                <div className="mabox-maintenance-field">
                  <span className="mabox-maintenance-field-label">{__("维护标题")}</span>
                  <Form.Item name="countdown_title" noStyle>
                    <Input aria-label={__("维护标题")} placeholder={__("例如：网站维护中")} />
                  </Form.Item>
                </div>

                <div className="mabox-maintenance-field">
                  <span className="mabox-maintenance-field-label">{__("背景图片（可选）")}</span>
                  <Form.Item name="countdown_image" noStyle>
                    <SelectImage
                      aria-label={__("维护背景图片")}
                      aria-describedby="mabox-maintenance-image-help"
                    />
                  </Form.Item>
                  <p id="mabox-maintenance-image-help" className="mabox-maintenance-field-help">
                    {__("不同模板的图片位置不同；全屏样式建议使用 1920×1080 像素图片。")}
                  </p>
                </div>

                <div className="mabox-maintenance-field">
                  <div className="mabox-maintenance-field-label-row">
                    <span className="mabox-maintenance-field-label">{__("维护说明")}</span>
                    <Popover
                      placement="rightTop"
                      title={__("HTML 示例")}
                      content={maintenanceHtmlExample}
                      trigger="click"
                    >
                      <Button
                        type="text"
                        shape="circle"
                        className="mabox-maintenance-info-button"
                        aria-label={__("查看维护说明 HTML 示例")}
                        icon={<InfoCircleOutlined />}
                      />
                    </Popover>
                  </div>
                  <Form.Item name="countdown_content" noStyle>
                    <TextAreaHtml
                      aria-label={__("维护说明")}
                      aria-describedby="mabox-maintenance-content-help"
                      rows={6}
                      placeholder={__("例如：抱歉，我们的网站正在维护中，请稍后再来。")}
                    />
                  </Form.Item>
                  <p id="mabox-maintenance-content-help" className="mabox-maintenance-field-help">
                    {__("支持安全 HTML；可通过信息按钮查看示例。")}
                  </p>
                </div>
              </div>
            </section>
          </div>
        </ModuleRow>
      </Form>
    </SettingsSection>
  );
};

//准备维护界面
import Default from "@/assets/page/function/service/default-simple.png";
import Default_img from "@/assets/page/function/service/default-image.png";
import Red from "@/assets/page/function/service/red-minimal.png";
const serviceList = [
  { value: "default", label: Default, title: "默认简洁" },
  { value: "default_img", label: Default_img, title: "默认带图" },
  { value: "red", label: Red, title: "红色纯粹" },
];

const maintenanceHtmlExample = (
  <div className="mabox-maintenance-html-example">
    <pre>{__(`<p>抱歉，我们的网站正在维护中...</p>
<h5 class="dull-text">
  请倒计时结束后再回来，我们准备了全新的内容哦！
</h5>`)}</pre>
  </div>
);

export default App;
