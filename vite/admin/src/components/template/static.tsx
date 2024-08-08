/**
 * 页面模版 静态
 */
import { useState, useContext, useEffect } from "react";
import { Form, Switch } from "antd";
import { DataContext } from "@/tool/dataContext";
import { TemplateStatic } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

type FieldType = TemplateStatic;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.template?.static || defaultVarOption.template.static;

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
    updateOption("template", "static", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="static"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>静态</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="立体三角"
          name="love"
          valuePropName="checked"
          extra={"展示高级质感的立体三角，可添加文本"}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
