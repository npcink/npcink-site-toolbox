import React, { useContext, useState, useEffect } from "react";
import { Select, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeMedium } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = OptimizeMedium;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData =
  optionData.optimize?.medium || defaultVarOption.optimize.medium;

  const [formData, setFormData] = useState(publicData);

  const onValuesChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevState) => ({ ...prevState, ...changedValues }));
  };

  useEffect(() => {
    updateOption("optimize", "medium", formData);
  }, [formData]);

  return (
    <SettingsSection title="媒体" description="媒体文件相关优化">
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
        <ModuleRow
          title="图片自动添加 Alt 标签"
          description="标签值为：当前文章名 - 网站名"
          featureId="optimize-medium-img_add_tag"
          enabled={formData.img_add_tag as boolean}
          onChange={(checked: boolean) => onValuesChange({ img_add_tag: checked } as Partial<FieldType>)}
        />
        <ModuleRow
          title="禁用自动图片尺寸"
          description="禁用自动生成的图片尺寸、禁用缩略尺寸，可能导致部分主题或插件无法获取所需尺寸"
          featureId="optimize-medium-no_auto_size"
          enabled={formData.no_auto_size as boolean}
          onChange={(checked: boolean) => onValuesChange({ no_auto_size: checked } as Partial<FieldType>)}
          tags={["高风险"]}
        />
        <ModuleRow
          title="添加SVG图标支持"
          description="选中后可在媒体库上传SVG图标，SVG文件可能包含脚本，存在安全风险"
          featureId="optimize-medium-medium_add_svg"
          enabled={formData.medium_add_svg as boolean}
          onChange={(checked: boolean) => onValuesChange({ medium_add_svg: checked } as Partial<FieldType>)}
          tags={["谨慎"]}
        />
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
    </SettingsSection>
  );
};

export default App;
