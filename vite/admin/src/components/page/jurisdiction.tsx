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

type FieldType = PageJurisdiction;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //准备默认值
  const optionObj = useContext(DataContext) ?? { page: {} };
  const publicData =
    optionObj.page?.jurisdiction || defaultVarOption.page.jurisdiction;

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

  //修改公共值
  useEffect(() => {
    optionObj.page = {
      ...optionObj.page,
      jurisdiction: formData,
    };
  }, [formData]);

  //存储表单值
  interface TagData {
    categorys: ListData[];
    tags: ListData[];
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
          <h2>未登录权限</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="隐藏指定分类"
          name="category_id"
          extra={"该分类下的内容未登录，不可见"}
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
          label="隐藏指定标签"
          name="tag_id"
          extra={"该标签下的内容未登录，不可见"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的标签"
            options={tagArray?.tags}
          />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
