//其他
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeAdmin } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

//选项类型
type FieldType = OptimizeAdmin;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData = optionData.optimize?.admin || defaultVarOption.optimize.admin;

  //创建变量并设默认值
  const [formData, setFormData] = useState(publicData || {});

  //表单同步修改值
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
    updateOption("optimize", "admin", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="admin"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={publicData}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={() => {}}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>后台-文章</h2>
        </Form.Item>

        <Form.Item<FieldType>
          id="optimize-admin-add_user"
          label="添加作者筛选项"
          name="add_user"
          valuePropName="checked"
          extra={"文章菜单添加作者筛选项"}
        >
          <FeatureSwitch featureId="optimize-admin-add_user" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-admin-add_time"
          label="添加时间筛选项"
          name="add_time"
          valuePropName="checked"
          extra={"文章和媒体菜单添加时间筛选项，媒体菜单需为列表布局"}
        >
          <FeatureSwitch featureId="optimize-admin-add_time" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-admin-show_id"
          label="各个列表显示链接ID"
          name="show_id"
          valuePropName="checked"
          extra={"支持 文章、页面、链接、多媒体、评论、分类、标签、用户 等"}
        >
          <FeatureSwitch featureId="optimize-admin-show_id" />
        </Form.Item>
        <Form.Item<FieldType>
          id="optimize-admin-thumbnail_switcher"
          label="缩略图切换"
          name="thumbnail_switcher"
          valuePropName="checked"
          extra={"展示、添加、删除缩略图"}
        >
          <FeatureSwitch featureId="optimize-admin-thumbnail_switcher" />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
