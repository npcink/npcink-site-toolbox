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

const fromConfig = AntConfig.from;

const templateItems = [
  {
    name: "立体三角",
    fieldName: "triangle" as const,
    featureId: "template-static-triangle",
    description: "展示高级质感的立体三角，底部是文章正文内容",
    preview: { title: "立体三角", img: TrianglePng },
  },
];

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.template?.static || defaultVarOption.template.static;

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
          <h2>静态模板</h2>
        </Form.Item>

        {templateItems.map((item) => (
          <div
            key={item.fieldName}
            id={item.featureId}
            className="mabox-template-row"
          >
            <div className="mabox-template-row-info">
              <div className="mabox-template-row-name">{item.name}</div>
              <div className="mabox-template-row-desc">{item.description}</div>
            </div>
            <div className="mabox-template-row-actions">
              <Form.Item<FieldType>
                name={item.fieldName}
                valuePropName="checked"
                noStyle
              >
                <FeatureSwitch featureId={item.featureId} />
              </Form.Item>
              {item.preview && (
                <Preview title={item.preview.title} img={item.preview.img} />
              )}
            </div>
          </div>
        ))}
      </Form>
    </>
  );
};

export default App;