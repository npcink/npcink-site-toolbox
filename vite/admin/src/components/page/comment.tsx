/**
 * 页面优化 - 评论
 */
import { useState, useContext, useEffect } from "react";
import { Form, Switch, InputNumber } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageComment } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

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
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
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
          label="添加OWO表情包"
          name="comment_emote"
          valuePropName="checked"
          extra={"评论区添加OWO表情包"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
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
          <Switch />
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
          <Switch />
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
          label="禁止纯英文评论"
          name="english"
          valuePropName="checked"
          extra={
            <a href="https://www.npc.ink/18129.html?mami" target="_blank">
              详细信息
            </a>
          }
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="单篇文章仅限评论一次"
          name="only"
          valuePropName="checked"
          extra={"管理员不受此影响"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="安全 - 移除评论中的管理员ID"
          name="modify_comment_user"
          valuePropName="checked"
          extra={"默认的评论样式中，会包含管理员登录ID，移除后，可提升安全性"}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
