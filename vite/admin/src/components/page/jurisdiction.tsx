/**
 * 页面优化 - 权限
 */
import { useState, useContext, useEffect } from "react";
import { Form, Select, Switch } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageJurisdiction, ListData } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { getCategoryData } from "@/axios/axios";
import TextAreaHtml from "@/basic/htmlInput";

type FieldType = PageJurisdiction;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //准备选项默认值
  const publicData =
    optionData.page?.jurisdiction || defaultVarOption.page.jurisdiction;

  //存储表单值
  const [formData, setFormData] = useState(publicData || {});

  //修改表单值
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
    updateOption("page", "jurisdiction", formData);
  }, [formData]);

  //存储表单值
  interface TagData {
    categorys: ListData[];//分类数组
    tags: ListData[];//标签数组
    pages:ListData[];//页面数组
  }
  const [tagArray, setTagArray] = useState<TagData>();
  //获取分类数组
  const getData = async () => {
    try {
      // 获取原始数据
      const list = await getCategoryData();
      //console.log(list);
      setTagArray(list);
    } catch (error) {
      console.error("Error fetching table data:", error);
    }
  };
  //加载页面自动获取数据
  useEffect(() => {
    // 在页面加载完成后执行 函数，获取数据并更新状态
    getData();
  }, []);

  return (
    <>
      <Form
        name="jurisdiction"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>权限</h2>
        </Form.Item>
        <Form.Item>
          <h3 className="menu-header">隐私权限</h3>
        </Form.Item>
        <Form.Item<FieldType>
          label="禁止在微信中打开"
          name="ban_open_weixing"
          extra={<>（可能有防红功能）</>}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="禁止在 QQ 中打开"
          name="ban_open_qq"
          extra={<>（可能有防红功能）</>}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType> label="禁止复制" name="ban_copy">
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="禁用F12前端调试"
          name="front_debug"
          extra={<>打开浏览器控制台显示空白内容</>}
        >
          <Switch />
        </Form.Item>
        <Form.Item>
          <h3 className="menu-header">未登录权限</h3>
        </Form.Item>

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
        {/**TODO:要不要预先提供几个模版 */}
        <Form.Item<FieldType>
          label="隐藏时的提示内容"
          name="tip_content"
          extra={"内容被隐藏时的提示内容，支持HTML"}
        >
          <TextAreaHtml />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
