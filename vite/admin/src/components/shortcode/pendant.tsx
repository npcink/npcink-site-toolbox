/**
 * 短代码 挂件
 */
import { useState, useContext, useEffect } from "react";
import { Form, Switch, Popover } from "antd";
import { DataContext } from "@/tool/dataContext";
import { CodePendant } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import Map from "@/basic/mapTable";
import Zuji from "@/assets/shortcode/pendant/足迹.png";

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
          extra={
            <>
              "在简单的中国地图上展示你的足迹"，
              <Popover content={<img src={Zuji} width={200} />} title="预览">
                效果预览
              </Popover>
            </>
          }
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
                  <a href="https://jingweidu.bmcx.com/" target="_blank">
                    经纬度查询
                  </a>
                </>
              }
            >
              <Map />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
