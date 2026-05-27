import { useState, useContext, useEffect } from "react";
import { Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { TemplateTrends } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

type FieldType = TemplateTrends;

const fromConfig = AntConfig.from;

const templateItems = [
  {
    name: "专题列表",
    fieldName: "special" as const,
    featureId: "template-trends-special",
    description: "搜索包含指定关键词的标题组成列表",
  },
];

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.template?.trends || defaultVarOption.template.trends;

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
          <h2>动态模板</h2>
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
            </div>
          </div>
        ))}
      </Form>
    </>
  );
};

export default App;