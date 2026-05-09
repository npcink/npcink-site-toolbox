/**
 * 页面模版 动态
 */
import { useState, useContext, useEffect } from "react";
import { Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { TemplateTrends } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

type FieldType = TemplateTrends;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.template?.trends || defaultVarOption.template.trends;

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
    updateOption("template", "trends", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="trends"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>动态</h2>
        </Form.Item>

        <Form.Item<FieldType>
          id="template-trends-special"
          label="专题"
          name="special"
          valuePropName="checked"
          extra={"搜索包含指定关键词的标题组成列表"}
        >
          <FeatureSwitch featureId="template-trends-special" />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
