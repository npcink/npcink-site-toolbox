import { useState, useContext, useEffect } from "react";
import { Form } from "antd";

import { DataContext } from "@/tool/dataContext";
import { LoginBeautify } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = LoginBeautify;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData =
    optionData.login?.beautify || defaultVarOption.login.beautify;

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
    updateOption("login", "beautify", formData);
  }, [formData]);

  return (
    <SettingsSection title="美化">
      <Form
        name="login_beautify"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="LOGO链接"
          description="改为首页链接"
          featureId="login-beautify-modify_login_link"
          enabled={formData.modify_login_link as boolean}
          onChange={(checked: boolean) => onValuesChange({ modify_login_link: checked } as Partial<FieldType>, { ...formData, modify_login_link: checked } as FieldType)}
        />
        <ModuleRow
          title="移除语言选择框"
          description="移除登录页面语言选择框"
          featureId="login-beautify-remove_langue"
          enabled={formData.remove_langue as boolean}
          onChange={(checked: boolean) => onValuesChange({ remove_langue: checked } as Partial<FieldType>, { ...formData, remove_langue: checked } as FieldType)}
        />
      </Form>
    </SettingsSection>
  );
};

export default App;
