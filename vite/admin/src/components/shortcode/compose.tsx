/**
 * 短代码 功能
 */
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import { DataContext } from "@/tool/dataContext";
import { CodeCompose } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import Preview from "@/basic/preview";
import FeatureSwitch from "@/basic/feature-switch";
import Runcode from "@/assets/shortcode/compose/运行代码.png";
import SingleList from "@/assets/shortcode/compose/文章列表.png";
import CopyBtn from "@/assets/shortcode/compose/复制按钮.png";
type FieldType = CodeCompose;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.shortcode?.compose || defaultVarOption.shortcode.compose;

  //存储表单值
  const [formData, setFormData] = useState(publicData || {});

  //修改表单值
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
    updateOption("shortcode", "compose", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="compose"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>板式</h2>
        </Form.Item>

        <Form.Item<FieldType>
          id="shortcode-compose-single_list"
          label="文章列表"
          name="single_list"
          valuePropName="checked"
          extra={
            <>
              "填写若干文章 ID 就能生成漂亮的文章列表"，
              <Preview title="文章列表" img={SingleList} />
            </>
          }
        >
          <FeatureSwitch featureId="shortcode-compose-single_list" />
        </Form.Item>
        <Form.Item<FieldType>
          id="shortcode-compose-single_copy"
          label="复制"
          name="single_copy"
          valuePropName="checked"
          extra={
            <>
              "第一个属性是按钮名称，第二个属性是弹窗内容，第三个属性是跳转网址"，
              <Preview title="复制按钮" img={CopyBtn} />
            </>
          }
        >
          <FeatureSwitch featureId="shortcode-compose-single_copy" />
        </Form.Item>
        <Form.Item<FieldType>
          id="shortcode-compose-runcode"
          label="前端运行代码"
          name="runcode"
          valuePropName="checked"
          extra={
            <>
              1、仅支持经典编辑器，2、[runcode]和[/runcode]不能换行，会有换行符,
              <pre className="pre-meat">&lt;runcode&gt;&lt;/runcode&gt;</pre>，
              <Preview title="在线运行前端代码" img={Runcode} />
            </>
          }
        >
          <FeatureSwitch featureId="shortcode-compose-runcode" />
        </Form.Item>
        <Form.Item<FieldType>
          id="shortcode-compose-bilibili"
          label="Bilibili 视频"
          name="bilibili"
          valuePropName="checked"
          extra={
            <>
              使用 <code>[mabox_bilibili bvid="BV号"]</code> 嵌入 B 站视频，无广告播放
            </>
          }
        >
          <FeatureSwitch featureId="shortcode-compose-bilibili" />
        </Form.Item>
        <Form.Item<FieldType>
          id="shortcode-compose-wx_unlock"
          label="公众号解锁"
          name="wx_unlock"
          valuePropName="checked"
          extra={"用户输入验证码后解锁隐藏内容，用于公众号引流"}
        >
          <FeatureSwitch featureId="shortcode-compose-wx_unlock" />
        </Form.Item>
        {formData.wx_unlock && (
          <>
            <Form.Item<FieldType> label="公众号名称" name="wx_unlock_name">
              <Input style={{ width: "50%" }} placeholder="例如：NPCink" />
            </Form.Item>
            <Form.Item<FieldType> label="公众号二维码" name="wx_unlock_qrcode">
              <Input style={{ width: "70%" }} placeholder="上传二维码后的图片地址" />
            </Form.Item>
            <Form.Item<FieldType>
              label="验证码列表"
              name="wx_unlock_codes"
              extra={"每行一个验证码，用户关注后发送关键词获取"}
            >
              <Input.TextArea rows={4} placeholder={"ABC123&#10;DEF456"} />
            </Form.Item>
            <Form.Item<FieldType> label="解锁提示" name="wx_unlock_tip">
              <Input style={{ width: "70%" }} placeholder="关注公众号获取验证码" />
            </Form.Item>
            <Form.Item<FieldType> label="关键词提示" name="wx_unlock_keyword_tip">
              <Input style={{ width: "70%" }} placeholder="关注公众号，发送关键词获取验证码" />
            </Form.Item>
          </>
        )}
        <Form.Item<FieldType>
          id="shortcode-compose-reward"
          label="打赏模块"
          name="reward"
          valuePropName="checked"
          extra={"文章末尾添加打赏按钮，支持微信/支付宝收款码弹窗展示"}
        >
          <FeatureSwitch featureId="shortcode-compose-reward" />
        </Form.Item>
        {formData.reward && (
          <>
            <Form.Item<FieldType> label="微信收款码" name="reward_wx_qr">
              <Input style={{ width: "70%" }} placeholder="上传微信收款码图片地址" />
            </Form.Item>
            <Form.Item<FieldType> label="支付宝收款码" name="reward_ali_qr">
              <Input style={{ width: "70%" }} placeholder="上传支付宝收款码图片地址" />
            </Form.Item>
            <Form.Item<FieldType> label="弹窗标题" name="reward_title">
              <Input style={{ width: "50%" }} placeholder="感谢您的支持" />
            </Form.Item>
            <Form.Item<FieldType> label="按钮文字" name="reward_btn_text">
              <Input style={{ width: "30%" }} placeholder="打赏" />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
