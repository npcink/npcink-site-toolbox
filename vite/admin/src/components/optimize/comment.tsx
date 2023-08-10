//评论
import React, { useContext, useState, useEffect } from "react";
import { Switch, Form, InputNumber } from "antd";
import DataContext from "@/tool/dataContext";
import { OptimizeComment } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";

//选项类型
type FieldType = OptimizeComment;

const App: React.FC = () => {
  //拿到公共值
  const optionObj = useContext(DataContext) || { optimize: {} };

  //简化并提供默认值
  let publicData = optionObj.optimize?.comment || defaultVar.optimize.comment;

  //拿到需要的默认值
  const [formData, setFormData] = useState(publicData);

  //表单同步值
  const onValuesChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevState) => ({ ...prevState, ...changedValues }));
  };

  // 表单值发生变化时更新dataContext的值
  useEffect(() => {
    optionObj.optimize = {
      ...optionObj.optimize,
      comment: formData,
    };
  }, [formData]);

  return (
    <Form
      name="comment"
      labelCol={{ span: 8 }}
      wrapperCol={{ span: 16 }}
      style={{ maxWidth: 800 }}
      initialValues={publicData}
      autoComplete="off"
      onFinish={() => {}}
      onValuesChange={onValuesChange}
    >
      <Form.Item>
        <h2>评论</h2>
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
          label="时间间隔(秒)"
          name="interval_time"
          extra={"指定时间后才能再次评论"}
        >
          <InputNumber min={0} />
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
            <InputNumber min={0} />
          </Form.Item>
          <Form.Item<FieldType> label="最大字数" name="words_number_max">
            <InputNumber min={0} />
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
    </Form>
  );
};

export default App;
