/**
 * 页面优化 - 权限
 */
import { useState, useContext, useEffect } from "react";
import { Form, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageJurisdiction, ListData } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { getCategoryData } from "@/axios/axios";
import TextAreaHtml from "@/basic/htmlInput";
import { SettingsSection } from "@/components/settings-ui";

type FieldType = PageJurisdiction;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.page?.jurisdiction || defaultVarOption.page.jurisdiction;
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
    updateOption("page", "jurisdiction", formData);
  }, [formData]);

  interface TagData {
    categorys: ListData[];
    tags: ListData[];
    pages: ListData[];
  }
  const [tagArray, setTagArray] = useState<TagData>();
  const getData = async () => {
    try {
      const list = await getCategoryData();
      setTagArray(list);
    } catch (error) {
      console.error("Error fetching table data:", error);
    }
  };
  useEffect(() => {
    getData();
  }, []);

  return (
    <SettingsSection title="权限">
      <Form
        name="jurisdiction"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <h3 className="mabox-menu-header">未登录权限</h3>

        <Form.Item<FieldType>
          label="隐藏指定分类下的内容"
          name="category_id"
          extra={"该分类下的内容未登录时，不可见，仅展示提示内容"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的分类"
            options={tagArray?.categorys}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label="隐藏指定标签下的内容"
          name="tag_id"
          extra={"该标签下的内容未登录时，不可见，仅展示提示内容"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的标签"
            options={tagArray?.tags}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label="隐藏指定页面"
          name="page_id"
          extra={"该页面下的内容未登录时，不可见，仅展示提示内容"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的页面"
            options={tagArray?.pages}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label="隐藏时的提示内容"
          name="tip_content"
          extra={"内容被隐藏时的提示内容，支持HTML"}
        >
          <TextAreaHtml />
        </Form.Item>
      </Form>
    </SettingsSection>
  );
};

export default App;
