import React, { useContext, useState, useEffect } from "react";
import { Switch, Select, Form } from "antd";
import DataContext from "@/tool/dataContext";
import { OptimizeMedium } from "@/tool/interface";
import option from "@/tool/defaultVar";

//选项类型
type FieldType = OptimizeMedium;

const App: React.FC = () => {

  //拿到公共值
  const optionObj = useContext(DataContext) || { optimize: {} };

  //提供默认值
  if (!optionObj.optimize.medium) {
    optionObj.optimize.medium = option.optimize.medium;
  }
  //拿到需要的媒体值
  const [FormData, setFormData] = useState(optionObj.optimize.medium);

  //表单同步值
  const onValuesChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevState) => ({ ...prevState, ...changedValues }));
  };

  //打印修改后的值
  const printData = (value: FieldType) => {
    console.log(value);
    console.log(optionObj);
  };

  //修改公共值
  useEffect(() => {
    optionObj.optimize.medium = FormData;
  }, [FormData]);

  return (
    <Form
      name="medium"
      labelCol={{ span: 8 }}
      wrapperCol={{ span: 16 }}
      style={{ maxWidth: 800 }}
      initialValues={optionObj.optimize.medium}
      autoComplete="off"
      onFinish={printData}
      onValuesChange={onValuesChange}
    >
      <Form.Item>
        <h2>媒体</h2>
      </Form.Item>

      <Form.Item<FieldType>
        label="图片自动添加 Alt 标签"
        name="img_add_tag"
        valuePropName="checked"
        extra={"标签值为：当前文章名 - 网站名"}
      >
        <Switch />
      </Form.Item>
      <Form.Item<FieldType>
        label="禁用自动生成的图片尺寸"
        name="no_auto_size"
        valuePropName="checked"
        extra={"禁用自动生成的图片尺寸、禁用缩放尺寸、禁用其他图片尺寸"}
      >
        <Switch />
      </Form.Item>
      <Form.Item<FieldType>
        label="添加媒体库SVG图标支持"
        name="medium_add_svg"
        valuePropName="checked"
        extra={"选中后可在媒体库上传SVG图标"}
      >
        <Switch />
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
      <Form.Item>
        <button>打印</button>
      </Form.Item>
    </Form>
  );
};

export default App;
