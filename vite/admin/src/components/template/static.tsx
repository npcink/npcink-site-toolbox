/**
 * 页面模版 静态
 */
import { useState, useContext, useEffect } from "react";
import { Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { TemplateStatic } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import Preview from "@/basic/preview";
import TrianglePng from "@/assets/template/static/立体三角.png";
import FeatureSwitch from "@/basic/feature-switch";

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
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
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
          id="template-static-triangle"
          label="立体三角"
          name="triangle"
          valuePropName="checked"
          extra={
            <>
              展示高级质感的立体三角，底部是文章正文内容，
              <Preview title="立体三角" img={TrianglePng} />
            </>
          }
        >
          <FeatureSwitch featureId="template-static-triangle" />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
