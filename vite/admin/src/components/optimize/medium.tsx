import React, { useContext, useState, useEffect } from "react";
import { Select, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeMedium } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";

type FieldType = OptimizeMedium;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData =
  optionData.optimize?.medium || defaultVarOption.optimize.medium;

  const [formData, setFormData] = useState(publicData);
  const webpSupported = window.dataLocal?.webpSupported;

  const onValuesChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevState) => ({ ...prevState, ...changedValues }));
  };

  useEffect(() => {
    updateOption("optimize", "medium", formData);
  }, [formData]);

  return (
    <SettingsSection title={__("媒体")} description={__("媒体文件相关优化")}>
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
          title={__("图片自动添加 Alt 标签")}
          description={__("标签值为：当前文章名 - 网站名")}
          featureId="optimize-medium-img_add_tag"
          enabled={formData.img_add_tag as boolean}
          onChange={(checked: boolean) => onValuesChange({ img_add_tag: checked } as Partial<FieldType>)}
        />
        <ModuleRow
          title={__("禁用自动图片尺寸")}
          description={__("禁用自动生成的图片尺寸、禁用缩略尺寸，可能导致部分主题或插件无法获取所需尺寸")}
          featureId="optimize-medium-no_auto_size"
          enabled={formData.no_auto_size as boolean}
          onChange={(checked: boolean) => onValuesChange({ no_auto_size: checked } as Partial<FieldType>)}
          tags={["高风险"]}
        />
        <ModuleRow
          title={__("添加SVG图标支持")}
          description={__("选中后可在媒体库上传SVG图标，SVG文件可能包含脚本，存在安全风险")}
          featureId="optimize-medium-medium_add_svg"
          enabled={formData.medium_add_svg as boolean}
          onChange={(checked: boolean) => onValuesChange({ medium_add_svg: checked } as Partial<FieldType>)}
          tags={["谨慎"]}
        />
        <ModuleRow
          title={__("新生成图片使用 WebP")}
          description={webpSupported === false
            ? __("当前服务器的 WordPress 图片编辑器不支持 WebP；即使保留设置，JPEG 也会安全保持原格式")
            : __("原始上传的 JPEG 留作恢复备份；媒体库使用 WordPress 生成的 WebP 主图和缩略图。不转换 PNG，不删除或覆盖原图")}
          featureId="optimize-medium-webp_conversion"
          enabled={formData.webp_conversion as boolean}
          onChange={(checked: boolean) => onValuesChange({ webp_conversion: checked } as Partial<FieldType>)}
          tags={webpSupported === false ? ["异常"] : ["性能", "安全"]}
        />
        <Form.Item<FieldType>
          label={__("上传图片自动重命名")}
          name="upload_auto_name"
          extra={
            <p>
              {__("数字重命名类似：")}<code>2023030303095446</code>，<br />
              {__("MD5重命名类似：")}<code>a9193c211c6c991528f29fb7acfee31a</code>
            </p>
          }
        >
          <Select
            style={{ width: 120 }}
            options={[
              { value: "false", label: __("禁用") },
              { value: "math", label: __("数字重命名") },
              { value: "md5", label: __("MD5重命名") },
            ]}
          />
        </Form.Item>
      </Form>
    </SettingsSection>
  );
};

export default App;
