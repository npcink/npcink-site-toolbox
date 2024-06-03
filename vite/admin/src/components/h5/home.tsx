//h5 - 首页
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form, Input, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { H5Home } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import type { SelectProps } from "antd";
import Contact from "@/components/h5/contact";
import { validateLink } from "@/tool/tool";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = H5Home;

//Ant 组件配置
const fromConfig = AntConfig.from;
const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  const publicData = optionData.h5?.home || defaultVarOption.h5.home;

  //创建变量并设默认值
  const [formData, setFormData] = useState(publicData);

  //表单同步修改值
  const onValuesChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevFormData) => ({
      ...prevFormData,
      ...changedValues,
    }));
  };

  // 表单值发生变化时更新dataContext的值
  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("h5", "home", formData);
  }, [formData]);

  //下拉选项
  //准备默认值

  //开发环境状态
  const state: boolean = import.meta.env.VITE_STATE;

  //获取文章
  const getSingleData = () => {
    if (state) {
      return [
        { label: "文章1", value: 1 },
        { label: "文章2", value: 2 },
        { label: "文章3", value: 3 },
      ];
    } else {
      return (window as any).dataLocal.single_arr !== ""
        ? (window as any).dataLocal.single_arr
        : [];
    }
  };
  //获取分类
  const getCatData = () => {
    if (state) {
      return [
        { label: "arrrts", value: 1 },
        { label: "tttt", value: 2 },
        { label: "大大怪", value: 3 },
      ];
    } else {
      return (window as any).dataLocal.cat_arr !== ""
        ? (window as any).dataLocal.cat_arr
        : [];
    }
  };

  //获取文章
  const SingleOption: SelectProps["options"] = getSingleData();

  const options: SelectProps["options"] = getCatData();

  return (
    <>
      <Form
        name="home"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
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
          <h2>H5介绍</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="开启功能"
          name="switch"
          valuePropName="checked"
          extra={
            <>
              使用WordPress提供的Rest API，
              <br />
              可通过H5单页来展示有趣的内容。
              <a href="https://www.npc.ink/276746.html?mima" target="_blank">
                详情介绍
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        {formData.switch && (
          <>
            <Form.Item>
              <h2>首页</h2>
            </Form.Item>
            {/**
             * 文章ID并可排序
             */}

            <Form.Item<FieldType> label="幻灯片文章选择" name="slide">
              <Select
                showSearch
                allowClear
                mode="multiple"
                options={SingleOption}
                optionFilterProp="label"
                filterOption={(input, option) =>
                  (typeof option?.label === "string" ? option.label : "")
                    .toLowerCase()
                    .includes(input.toLowerCase())
                }
              />
            </Form.Item>
            <Form.Item<FieldType>
              label="查看全部按钮的链接"
              name="slide_all"
              rules={[{ validator: validateLink }]}
            >
              <Input />
            </Form.Item>
            <Form.Item<FieldType> label="待展示分类" name="more">
              <Select
                showSearch
                allowClear
                options={options}
                optionFilterProp="label"
                filterOption={(input, option) =>
                  (typeof option?.label === "string" ? option.label : "")
                    .toLowerCase()
                    .includes(input.toLowerCase())
                }
              />
            </Form.Item>
          </>
        )}
      </Form>

      {/**
       * 其他设置组件
       */}
      {formData.switch && <Contact />}
    </>
  );
};

export default App;
