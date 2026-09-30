/**
 * 页面优化 - 权限
 */
import { useState, useContext, useEffect } from "react";
import { Alert, Button, Form, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageJurisdiction } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { CategoryData, getCategoryData } from "@/axios/axios";
import TextAreaHtml from "@/basic/htmlInput";
import { SettingsSection } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";

type FieldType = PageJurisdiction;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.page?.jurisdiction || defaultVarOption.page.jurisdiction;
  const { configEpoch } = useContext(DataContext);
  const [formData, setFormData] = useState(publicData || {});

  // configEpoch 在保存或重新读取成功后自增，把服务端（可能已自动修正）的值同步回表单
  useEffect(() => {
    setFormData(publicData || {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [configEpoch]);
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

  const [tagArray, setTagArray] = useState<CategoryData>();
  const [taxonomyLoading, setTaxonomyLoading] = useState(true);
  const [taxonomyError, setTaxonomyError] = useState(false);
  const getData = async () => {
    setTaxonomyLoading(true);
    setTaxonomyError(false);
    try {
      const list = await getCategoryData();
      setTagArray(list);
    } catch (error) {
      console.error("Error fetching category data:", error);
      setTaxonomyError(true);
    } finally {
      setTaxonomyLoading(false);
    }
  };
  useEffect(() => {
    void getData();
  }, []);

  return (
    <SettingsSection title={__("权限")}>
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
        <h3 className="mabox-menu-header">{__("未登录权限")}</h3>

        {taxonomyError && (
          <Alert
            type="error"
            showIcon
            role="alert"
            style={{ marginBottom: 16 }}
            message={__("分类、标签或页面列表加载失败。")}
            description={__("下方选项暂时无法选择，请检查网络或 REST 接口后重试。")}
            action={
              <Button size="small" onClick={() => void getData()}>
                {__("重试")}
              </Button>
            }
          />
        )}
        {taxonomyLoading && !taxonomyError && (
          <Alert
            type="info"
            showIcon
            role="status"
            style={{ marginBottom: 16 }}
            message={__("正在加载分类、标签和页面列表…")}
          />
        )}

        <Form.Item<FieldType>
          label={__("隐藏指定分类下的内容")}
          name="category_id"
          extra={__("该分类下的内容未登录时，不可见，仅展示提示内容")}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder={taxonomyError ? __("加载失败，请重试") : __("请选择要隐藏的分类")}
            loading={taxonomyLoading}
            disabled={taxonomyError}
            options={tagArray?.categorys}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label={__("隐藏指定标签下的内容")}
          name="tag_id"
          extra={__("该标签下的内容未登录时，不可见，仅展示提示内容")}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder={taxonomyError ? __("加载失败，请重试") : __("请选择要隐藏的标签")}
            loading={taxonomyLoading}
            disabled={taxonomyError}
            options={tagArray?.tags}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label={__("隐藏指定页面")}
          name="page_id"
          extra={__("该页面下的内容未登录时，不可见，仅展示提示内容")}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder={taxonomyError ? __("加载失败，请重试") : __("请选择要隐藏的页面")}
            loading={taxonomyLoading}
            disabled={taxonomyError}
            options={tagArray?.pages}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label={__("隐藏时的提示内容")}
          name="tip_content"
          extra={__("内容被隐藏时的提示内容，支持HTML")}
        >
          <TextAreaHtml />
        </Form.Item>
      </Form>
    </SettingsSection>
  );
};

export default App;
