//权限 - 辅助功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form, Input, Button, Space, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AuthorityAuxiliary } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = AuthorityAuxiliary;

//Ant 组件配置
const fromConfig = AntConfig.from;

//多行输入
const { TextArea } = Input;

const App: React.FC = () => {
  //拿到默认选项值
  const { authority: optionData } = useContext(DataContext) ?? {};
  const optionObj = { authority: optionData || {} };

  //简化并提供默认值
  let publicData =
    optionObj.authority?.auxiliary || defaultVarOption.authority.auxiliary;

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

  // 表单值发生变化时更新dataContext的值
  const dataContext = useContext(DataContext);
  useEffect(() => {
    //由于选项site可能不存在，这里需要使用复制来新建
    dataContext.authority = {
      ...dataContext.authority,
      auxiliary: formData,
    };
  }, [formData]);

  //提取百度统计标识符
  const handleValueChange = (e: { target: { value: any } }) => {
    //设为空值，避免报错
    if (!e) {
      return;
    }
    let value = e.target.value;
    let regex = /hm\.js\?([A-Za-z0-9]+)/;
    let match = value.match(regex);

    if (match) {
      return match[1];
    } else {
      // 处理失败，显示弹窗提示
      message.error("处理失败，请输入百度统计平台的完整统计代码");
      return ""; // 或者返回其他默认值
    }
  };

  //提取谷歌标识符
  const extract_google = (e: { target: { value: any } }) => {
    //设为空值，避免报错
    if (!e) {
      return;
    }
    let value = e.target.value;
    let regex =
      /<meta\s+.*?name="google-site-verification".*?content="([A-Za-z0-9_\-]+)".*?>/i;

    let match = value.match(regex);
    if (match) {
      return match[1];
    } else {
      message.error("处理失败，请输入谷歌平台完整 HTML 标记");
      return "";
    }
  };

  //提取必应标识符
  const extract_biying = (e: { target: { value: any } }) => {
    //设为空值，避免报错
    if (!e) {
      return;
    }
    let value = e.target.value;

    let regex =
      /<meta\s+.*?name="msvalidate\.01".*?content="([A-Za-z0-9]+)".*?>/i;
    let match = value.match(regex);
    if (match) {
      return match[1];
    } else {
      message.error("处理失败，请输入必应平台完整 HTML Meta 标记");
      return "";
    }
  };

  return (
    <>
      <Form
        name="auxiliary"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
        style={{ maxWidth: fromConfig.maxWidth }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={formData}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={() => {}}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>辅助功能</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="文章统计"
          name="single_count"
          valuePropName="checked"
          extra={"开启后显示在仪表盘下"}
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="屏蔽恶意关键词搜索"
          name="no_malice_key"
          valuePropName="checked"
          extra={"禁止搜索指定词汇"}
        >
          <Switch />
        </Form.Item>
        {formData.no_malice_key && (
          <Form.Item<FieldType>
            label="输入关键词"
            name="malice_keu_content"
            extra={"输入您的关键词，以“回车键”分隔，一行一个"}
          >
            <TextArea rows={4} placeholder="一行一个" />
          </Form.Item>
        )}
        {/**TODO:处理script标签 */}
        <Form.Item<FieldType>
          label="百度统计"
          name="baidu_tonji"
          getValueFromEvent={handleValueChange}
          extra={
            <p>
              <a
                href="https://tongji.baidu.com/main/setting/self/home/site/index"
                target="_blank"
              >
                百度统计
              </a>
              → 代码管理（左侧） → 代码获取 → 获取代码 →
              复制代码贴入输入框中并保存即可
            </p>
          }
        >
          <SiteInput />
        </Form.Item>
        <Form.Item<FieldType>
          label="谷歌统计"
          name="google_tonji"
          getValueFromEvent={extract_google}
          extra={
            <p>
              <a
                href="https://search.google.com/search-console/about"
                target="_blank"
              >
                谷歌统计
              </a>
              ：
              <pre className="pre-meat">
                &lt;meta name="google-site-verification" content="HB..." /&gt;
              </pre>
            </p>
          }
        >
          <SiteInput />
        </Form.Item>
        <Form.Item<FieldType>
          label="必应统计"
          name="biying_tonji"
          getValueFromEvent={extract_biying}
          extra={
            <p>
              <a href="https://www.bing.com/webmasters" target="_blank">
                必应统计
              </a>
              ：
              <pre className="pre-meat">
                &lt;meta name="msvalidate.01" content="CF..." /&gt;
              </pre>
            </p>
          }
        >
          <SiteInput />
        </Form.Item>
      </Form>
    </>
  );
};

//网址输入框
const SiteInput = (props: any) => {
  // const inputRef = useRef(null);

  //不能直接执行，得用函数装起来
  const handleReset = () => {
    props.onChange(""); //更新传出的值
  };

  return (
    <div>
      <Space.Compact style={{ width: "100%" }}>
        <Input {...props} placeholder="自动处理代码" />
        <Button onClick={handleReset}>清空</Button>
      </Space.Compact>
    </div>
  );
};

export default App;
