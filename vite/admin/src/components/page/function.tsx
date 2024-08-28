//页面 - 功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Switch,  Input, Radio, InputNumber } from "antd";
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

//选项类型
type FieldType = PageFunction;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData = optionData.page?.function || defaultVarOption.page.function;

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
    updateOption("page", "function", formData);
  }, [formData]);

  //const [form] = Form.useForm();

  return (
    <>
      <Form
        //form={form}
        name="function"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
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
          <h2>功能</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="彩色背景标签云"
          name="color_tag"
          valuePropName="checked"
          extra={"可在小工具中添加圆角彩色背景标签云，前台即可看到效果"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="首图作特色图"
          name="first_picture"
          valuePropName="checked"
          extra={<>初次发布文章，未设置特色图时，自动将第一张图设为特色图</>}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="文章内关键词添加内链"
          name="add_inks"
          valuePropName="checked"
          extra={
            <>
              文章内的内容与添加的标签相同，则添加对应标签的链接
              <a
                href="https://www.npc.ink/15286.html?=magick-mami"
                target="_blank"
              >
                详细介绍
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="移除文章内超链接"
          name="remove_single_link"
          valuePropName="checked"
          extra={"关闭此选项可恢复"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="未登录模糊文章内图片"
          name="no_login_img"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="添加最后更新时间"
          name="add_last_update"
          valuePropName="checked"
          extra={"文章末尾添加最后更新时间，文章发布24小时后再次修改，即可展示"}
        >
          <Switch />
        </Form.Item>
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
                  {/*TODO:支持HTML标签*/}
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
        <Form.Item<FieldType>
          label="分享"
          name="share"
          extra={<>开启侧边悬浮按钮，提供画报分享，复制链接，发送邮件等功能</>}
        >
          <Switch />
        </Form.Item>
        {formData.share && (
          <>
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
          </>
        )}

        <Form.Item<FieldType>
          label="简繁切换"
          name="switch_lang_jf"
          extra={<>屏幕右下角添加简体繁体切换按钮</>}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
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

const goLink = [
  { value: "zhihu", label: Zhihu, title: "知乎" },
  { value: "tencent", label: Tencent, title: "腾讯云" },
  { value: "shimo", label: Shimo, title: "石墨" },
  { value: "jianshu", label: Jianshu, title: "简书" },
  { value: "wx_community", label: Wx_community, title: "微信社区" },
  { value: "csdn", label: CSDN, title: "CSDN" },
  { value: "ssp", label: SSP, title: "少数派" },
];

//准备维护界面
import Default from "@/assets/page/function/service/默认简洁.png";
import Default_img from "@/assets/page/function/service/默认带图.png";
import Red from "@/assets/page/function/service/红色纯粹.png";
import Purple from "@/assets/page/function/service/紫色期待.png";
import Lighting from "@/assets/page/function/service/灯光聚焦.png";
import Masking from "@/assets/page/function/service/高级遮罩.png";
import Rotate from "@/assets/page/function/service/炫彩时钟.png";
const serviceList = [
  { value: "default", label: Default, title: "默认简洁" },
  { value: "default_img", label: Default_img, title: "默认带图" },
  { value: "red", label: Red, title: "红色纯粹" },
  { value: "purple", label: Purple, title: "紫色期待" },
  { value: "lighting", label: Lighting, title: "灯光聚焦" },
  { value: "masking", label: Masking, title: "高级遮罩" },
  { value: "rotate", label: Rotate, title: "炫彩时钟" },
];

export default App;
