import React, { useContext, useState, useEffect } from "react";
import { Select, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeMedium } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";
import { checkRiskyFeature } from "@/tool/riskyFeature.tsx";

//选项类型
type FieldType = OptimizeMedium;

//Ant 组件配置
const fromConfig = AntConfig.from;

const RISKY_FIELDS: Record<string, string> = {
  no_auto_size: "optimize-medium-no_auto_size",
};

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData =
  optionData.optimize?.medium || defaultVarOption.optimize.medium;

  //拿到需要的媒体值
  const [formData, setFormData] = useState(publicData);

  //表单同步值
  const onValuesChange = (changedValues: Partial<FieldType>) => {
    const fieldKey = Object.keys(changedValues)[0];
    const featureId = RISKY_FIELDS[fieldKey];
    if (featureId) {
      const newValue = changedValues[fieldKey as keyof FieldType];
      const shouldProceed = checkRiskyFeature(featureId, newValue, () => {
        setFormData((prevState) => ({ ...prevState, ...changedValues }));
      });
      if (!shouldProceed) {
        return;
      }
    }
    setFormData((prevState) => ({ ...prevState, ...changedValues }));
  };

  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("optimize", "medium", formData);
  }, [formData]);

  return (
    <Form
      name="medium"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onFinish={() => {}}
      onValuesChange={onValuesChange}
    >
      <Form.Item>
        <h2>媒体</h2>
      </Form.Item>

      <Form.Item<FieldType>
        id="optimize-medium-img_add_tag"
        label="图片自动添加 Alt 标签"
        name="img_add_tag"
        valuePropName="checked"
        extra={"标签值为：当前文章名 - 网站名"}
      >
        <FeatureSwitch featureId="optimize-medium-img_add_tag" />
      </Form.Item>
      <Form.Item<FieldType>
        id="optimize-medium-no_auto_size"
        label="禁用自动图片尺寸"
        name="no_auto_size"
        valuePropName="checked"
        extra={"禁用自动生成的图片尺寸、禁用缩放尺寸、禁用其他图片尺寸"}
      >
        <FeatureSwitch featureId="optimize-medium-no_auto_size" />
      </Form.Item>
      <Form.Item<FieldType>
        id="optimize-medium-medium_add_svg"
        label="添加SVG图标支持"
        name="medium_add_svg"
        valuePropName="checked"
        extra={"选中后可在媒体库上传SVG图标"}
      >
        <FeatureSwitch featureId="optimize-medium-medium_add_svg" />
      </Form.Item>
      <Form.Item<FieldType>
        label="上传图片自动重命名"
        name="upload_auto_name"
        extra={
          <p>
            数字重命名类似：<code>2023030303095446</code>，<br />
            MD5重命名类似<code>a9193c211c6c991528f29fb7acfee31a</code>
          </p>
        }
      >
        <Select
          style={{ width: 120 }}
          options={[
            { value: "false", label: "禁用" },
            { value: "math", label: "数字重命名" },
            { value: "md5", label: "MD5重命名" },
          ]}
        />
      </Form.Item>
    </Form>
  );
};

export default App;
