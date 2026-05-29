//页面 - 功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, Radio, InputNumber } from "antd";
import TimePeriod from "@/basic/timeInput";
import TextAreaHtml from "@/basic/htmlInput";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFunction } from "@/tool/interface";
import SelectImage from "@/basic/selectImage";
import FixedImage from "@/basic/fixedImage";
import Email from "@/assets/page/function/share/email.png";
import WeiBo from "@/assets/page/function/share/weibo.png";
import Preview from "@/basic/preview";
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
          title="移除文章内超链接"
          description="关闭此选项可恢复"
          featureId="page-function-remove_single_link"
          enabled={formData.remove_single_link as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ remove_single_link: checked } as Partial<FieldType>, formData);
          }}
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
          label="外链跳转中间页"
          name="go_middle"
          extra={
            <>
              文章中的外链会先跳转到中间页，再跳转第三方，
              <br />
              此选项仅外观不同，功能相同;
              <br /> 推荐给robots.txt添加内容：
              <pre className="pre-meat">Disallow: /go_to/=*</pre>
              屏蔽搜索引擎对中间页的抓取,
              <a href="https://www.dujin.org/12762.html" target="_blank">
                详情
              </a>
            </>
          }
        >
          <FixedImage alists={goLink} />
        </Form.Item>
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
                  <pre className="pre-meat">
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
        <ModuleRow
          title="分享"
          description="开启侧边悬浮按钮，提供画报分享，复制链接，发送邮件等功能"
          featureId="page-function-share"
          enabled={formData.share as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ share: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item label="分享">
            <h3>按钮位置</h3>
          </Form.Item>
          <Form.Item<FieldType> label="分享按钮位置" name="share_position">
            <Radio.Group
              options={[
                { label: "左边", value: "left" },
                { label: "右边", value: "right" },
              ]}
              optionType="button"
              buttonStyle="solid"
            />
          </Form.Item>
          <Form.Item<FieldType> label="按钮距离顶部" name="share_top">
            <InputNumber addonAfter="px" style={{ width: "120px" }} />
          </Form.Item>
          <Form.Item<FieldType> label="按钮距离侧边" name="share_margins">
            <InputNumber addonAfter="px" style={{ width: "120px" }} />
          </Form.Item>
          <Form.Item<FieldType>
            label="分享文本"
            name="share_text"
            extra={
              <>
                前往第三方平台分享时展示的文本：
                <Preview title="分享文本" img={WeiBo} />
              </>
            }
          >
            <Input />
          </Form.Item>
          <Form.Item label="分享">
            <h3>
              邮箱 -
              <Preview title="邮箱" img={Email} />
            </h3>
          </Form.Item>
          <Form.Item<FieldType>
            label="邮箱地址"
            name="share_email_email"
            rules={[
              {
                type: "email",
                message: "请输入有效的邮箱地址!",
              },
              {
                required: true,
                message: "请输入邮箱地址!",
              },
            ]}
          >
            <Input />
          </Form.Item>
          <Form.Item<FieldType> label="邮箱标题" name="share_email_title">
            <Input />
          </Form.Item>
          <Form.Item<FieldType> label="邮箱内容" name="share_email_content">
            <Input />
          </Form.Item>
          <Form.Item label="分享">
            <h3>主图</h3>
          </Form.Item>

          <Form.Item<FieldType> label="首页默认图" name="share_img_home">
            <SelectImage />
          </Form.Item>
          <Form.Item<FieldType> label="页面默认图" name="share_img_page">
            <SelectImage />
          </Form.Item>
          <Form.Item<FieldType> label="其他默认图" name="share_img_about">
            <SelectImage />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title="简繁切换"
          description="屏幕右下角添加简体繁体切换按钮"
          featureId="page-function-switch_lang_jf"
          enabled={formData.switch_lang_jf as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ switch_lang_jf: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title="进阶防刷"
          description="对频繁访问的异常 IP 触发腾讯防水墙验证"
          featureId="page-function-anti_crawler"
          enabled={formData.anti_crawler as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ anti_crawler: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item<FieldType>
            label="最大请求数"
            name="anti_crawler_max_requests"
            extra={"时间窗口内超过此次数将触发验证"}
          >
            <InputNumber addonAfter={"次"} style={{ width: "120px" }} min={10} />
          </Form.Item>
          <Form.Item<FieldType>
            label="时间窗口"
            name="anti_crawler_time_window"
            extra={"统计请求的时间范围"}
          >
            <InputNumber addonAfter={"秒"} style={{ width: "120px" }} min={10} />
          </Form.Item>
          <Form.Item<FieldType> label="腾讯防水墙 AppID" name="anti_crawler_tecent_id">
            <Input style={{ width: "50%" }} placeholder="腾讯防水墙 AppID" />
          </Form.Item>
          <Form.Item<FieldType> label="腾讯防水墙 AppKey" name="anti_crawler_tecent_key">
            <Input style={{ width: "50%" }} placeholder="腾讯防水墙 AppKey" />
          </Form.Item>
        </ModuleRow>



      </Form>
    </SettingsSection>
  );
};

//准备跳转链接用数组对象
import Zhihu from "@/assets/page/function/go/知乎.png";
import Tencent from "@/assets/page/function/go/腾讯云.png";
import Shimo from "@/assets/page/function/go/石墨文档.png";
import Jianshu from "@/assets/page/function/go/简书.png";
import Wx_community from "@/assets/page/function/go/微信社区.png";
import CSDN from "@/assets/page/function/go/CSDN.png";
import SSP from "@/assets/page/function/go/少数派.png";
import WPS from "@/assets/page/function/go/WPS.png";

const goLink = [
  { value: "zhihu", label: Zhihu, title: "知乎" },
  { value: "tencent", label: Tencent, title: "腾讯云" },
  { value: "shimo", label: Shimo, title: "石墨" },
  { value: "jianshu", label: Jianshu, title: "简书" },
  { value: "ssp", label: SSP, title: "少数派" },
  { value: "wx_community", label: Wx_community, title: "微信社区" },
  { value: "csdn", label: CSDN, title: "CSDN" },
  { value: "wps", label: WPS, title: "WPS" },
];

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
