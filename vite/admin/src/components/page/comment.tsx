/**
 * 页面优化 - 评论
 */
import { useState, useContext, useEffect } from "react";
import { Form, InputNumber, Input, Radio } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageComment } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

type FieldType = PageComment;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.page?.comment || defaultVarOption.page.comment;

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
    updateOption("page", "comment", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="comment"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>评论</h2>
        </Form.Item>

        <Form.Item<FieldType>
          id="page-comment-comment_emote"
          label="添加OWO表情包"
          name="comment_emote"
          valuePropName="checked"
          extra={"评论区添加OWO表情包"}
        >
          <FeatureSwitch featureId="page-comment-comment_emote" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-comment-interval"
          label="两次评论间隔时间"
          name="interval"
          valuePropName="checked"
          extra={
            <>
              避免短时间内重复灌水评论，对管理员无效,
              <a href="https://www.npc.ink/19960.html?mami" target="_blank">
                详细信息
              </a>
            </>
          }
        >
          <FeatureSwitch featureId="page-comment-interval" />
        </Form.Item>
        {formData.interval && (
          <Form.Item<FieldType>
            label="时间间隔"
            name="interval_time"
            extra={"指定时间后才能再次评论"}
          >
            <InputNumber min={0} addonAfter="秒" />
          </Form.Item>
        )}
        <Form.Item<FieldType>
          id="page-comment-words_number"
          label="限制评论字数"
          name="words_number"
          valuePropName="checked"
          extra={
            <>
              指定最小和最大评论字数，
              <a href="https://www.npc.ink/17995.html?mami" target="_blank">
                详细信息
              </a>
            </>
          }
        >
          <FeatureSwitch featureId="page-comment-words_number" />
        </Form.Item>
        {formData.words_number && (
          <>
            <Form.Item<FieldType> label="最小字数" name="words_number_min">
              <InputNumber min={0} addonAfter="字" />
            </Form.Item>
            <Form.Item<FieldType> label="最大字数" name="words_number_max">
              <InputNumber min={0} addonAfter="字" />
            </Form.Item>
          </>
        )}

        <Form.Item<FieldType>
          id="page-comment-english"
          label="禁止纯英文评论"
          name="english"
          valuePropName="checked"
          extra={
            <a href="https://www.npc.ink/18129.html?mami" target="_blank">
              详细信息
            </a>
          }
        >
          <FeatureSwitch featureId="page-comment-english" />
        </Form.Item>

        <Form.Item<FieldType>
          id="page-comment-only"
          label="单篇文章仅限评论一次"
          name="only"
          valuePropName="checked"
          extra={"管理员不受此影响"}
        >
          <FeatureSwitch featureId="page-comment-only" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-comment-modify_comment_user"
          label="安全 - 移除评论中的管理员ID"
          name="modify_comment_user"
          valuePropName="checked"
          extra={"默认的评论样式中，会包含管理员登录ID，移除后，可提升安全性"}
        >
          <FeatureSwitch featureId="page-comment-modify_comment_user" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-comment-sensitive_words"
          label="敏感词过滤"
          name="sensitive_words"
          valuePropName="checked"
          extra={"评论提交时检测敏感词，替换或拦截"}
        >
          <FeatureSwitch featureId="page-comment-sensitive_words" />
        </Form.Item>
        {formData.sensitive_words && (
          <>
            <Form.Item<FieldType>
              label="敏感词列表"
              name="sensitive_words_list"
              extra={"每行一个敏感词"}
            >
              <Input.TextArea rows={6} placeholder={"敏感词1&#10;敏感词2"} />
            </Form.Item>
            <Form.Item<FieldType>
              label="处理方式"
              name="sensitive_words_action"
            >
              <Radio.Group>
                <Radio value="replace">替换为 ***</Radio>
                <Radio value="block">拦截并阻止提交</Radio>
              </Radio.Group>
            </Form.Item>
            <Form.Item<FieldType>
              label="替换字符"
              name="sensitive_words_replace_char"
              extra={"选择替换方式时生效"}
            >
              <Input style={{ width: "30%" }} placeholder="***" />
            </Form.Item>
          </>
        )}
        <Form.Item<FieldType>
          id="page-comment-baidu_moderation"
          label="百度文本审核"
          name="baidu_moderation"
          valuePropName="checked"
          extra={"接入百度AI内容审核API，自动审核评论"}
        >
          <FeatureSwitch featureId="page-comment-baidu_moderation" />
        </Form.Item>
        {formData.baidu_moderation && (
          <>
            <Form.Item<FieldType> label="API Key" name="baidu_moderation_api_key">
              <Input style={{ width: "50%" }} placeholder="百度AI开放平台 API Key" />
            </Form.Item>
            <Form.Item<FieldType> label="Secret Key" name="baidu_moderation_secret_key">
              <Input style={{ width: "50%" }} placeholder="百度AI开放平台 Secret Key" />
            </Form.Item>
            <Form.Item<FieldType>
              label="审核不通过处理"
              name="baidu_moderation_action"
            >
              <Radio.Group>
                <Radio value="mark">标记为待审核</Radio>
                <Radio value="block">直接拦截</Radio>
              </Radio.Group>
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
