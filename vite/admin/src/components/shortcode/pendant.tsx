/**
 * 短代码 挂件
 */
import { useState, useContext, useEffect } from "react";
import { Form, Switch, Table } from "antd";
import type { TableProps } from "antd";
import { DataContext } from "@/tool/dataContext";
import { CodePendant } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

type FieldType = CodePendant;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.shortcode?.pendant || defaultVarOption.shortcode.pendant;

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
    updateOption("shortcode", "pendant", formData);
  }, [formData]);

  //数据类型
  interface DataType {
    name: string;
    latLng: [number, number];
  }

  //演示用数据 - 足迹位置
  const markers: DataType[] = [
    // 足迹位置

    {
      latLng: [31.4, 121.48],
      name: "上海",
    },
    {
      latLng: [39.09, 117.2],
      name: "天津",
    },
    {
      latLng: [22.54, 114.06],
      name: "深圳",
    },
  ];
  //表头
  const columns: TableProps<DataType>["columns"] = [
    {
      title: "地址",
      dataIndex: "name",
    },
    {
      title: "经纬度",
      dataIndex: "latLng",
      render: (text) => (
        <p>
          [{text[0]} , {text[1]}]
        </p>
      ),
    },
  ];

  return (
    <>
      <Form
        name="pendant"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>挂件</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="足迹地图"
          name="merc_map"
          valuePropName="checked"
          extra={"在简单的中国地图上展示你的足迹"}
        >
          <Switch />
        </Form.Item>
        {formData.merc_map && (
          <>
            <Form.Item<FieldType>
              label="地点"
              name="merc_location"
              extra={
                <>
                  需填写地址和经纬度，保留两位小数
                  <a href="https://lbs.amap.com/tools/picker">高德坐标拾取</a>
                </>
              }
            >
              <Table
                dataSource={markers}
                columns={columns}
                bordered
                size="small"
              />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
