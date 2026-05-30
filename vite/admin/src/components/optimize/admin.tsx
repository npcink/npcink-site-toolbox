import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeAdmin } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = OptimizeAdmin;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData = optionData.optimize?.admin || defaultVarOption.optimize.admin;

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
    updateOption("optimize", "admin", formData);
  }, [formData]);

  return (
    <SettingsSection title="后台" description="后台文章管理增强">
      <Form
        name="admin"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="添加作者筛选项"
          description="文章菜单添加作者筛选项"
          featureId="optimize-admin-add_user"
          enabled={formData.add_user as boolean}
          onChange={(checked: boolean) => onValuesChange({ add_user: checked } as Partial<FieldType>, formData)}
        />
        <ModuleRow
          title="添加时间筛选项"
          description="文章和媒体菜单添加时间筛选项，媒体菜单需为列表布局"
          featureId="optimize-admin-add_time"
          enabled={formData.add_time as boolean}
          onChange={(checked: boolean) => onValuesChange({ add_time: checked } as Partial<FieldType>, formData)}
        />
        <ModuleRow
          title="各个列表显示链接ID"
          description="支持 文章、页面、链接、多媒体、评论、分类、标签、用户 等"
          featureId="optimize-admin-show_id"
          enabled={formData.show_id as boolean}
          onChange={(checked: boolean) => onValuesChange({ show_id: checked } as Partial<FieldType>, formData)}
        />
        <ModuleRow
          title="缩略图切换"
          description="展示、添加、删除缩略图，仅经典编辑器可用"
          featureId="optimize-admin-thumbnail_switcher"
          enabled={formData.thumbnail_switcher as boolean}
          onChange={(checked: boolean) => onValuesChange({ thumbnail_switcher: checked } as Partial<FieldType>, formData)}
          tags={["经典编辑器"]}
        />
      </Form>
    </SettingsSection>
  );
};

export default App;
