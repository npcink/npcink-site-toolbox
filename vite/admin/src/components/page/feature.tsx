//页面 - 外观优化
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Switch, Input, InputNumber } from "antd";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFeature } from "@/tool/interface";
import FixedImage from "@/basic/fixedImage";
import PixelChicken from "@/assets/page/feature/像素小鸡.png";
import Preview from "@/basic/preview";

//选项类型
type FieldType = PageFeature;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData = optionData.page?.feature || defaultVarOption.page.feature;

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

  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("page", "feature", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="aspect"
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
          <h2>外观</h2>
        </Form.Item>
        <Form.Item>
          <h3 className="menu-header">特效</h3>
        </Form.Item>
        <Form.Item<FieldType>
          label="动态标题"
          name="title"
          valuePropName="checked"
          extra={
            <>
              离开当前页面后，在标签页上显示有趣的文本，
              <a
                href="https://www.cnblogs.com/HaoranZing/p/16917421.html"
                target="_blank"
              >
                详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        {formData.title && (
          <>
            <Form.Item<FieldType> label="回到当前页" name="title_front">
              <Input style={{ width: "50%" }} />
            </Form.Item>
            <Form.Item<FieldType> label="离开当前页" name="title_after">
              <Input style={{ width: "50%" }} />
            </Form.Item>
          </>
        )}
        <Form.Item<FieldType>
          label="顶部加载进度条"
          name="top_loading"
          valuePropName="checked"
          extra={<>火狐浏览器不显示</>}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="全站变灰"
          name="site_grey"
          valuePropName="checked"
          extra={
            <>
              特殊时间下让网站变灰，有特别的意义，
              <a href="https://www.npc.ink/14874.html" target="_blank">
                实现详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="平滑滚动"
          name="page_scrolling"
          valuePropName="checked"
          extra={"让页面滚动起来更丝滑，部分浏览器不支持"}
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="点击特效"
          name="particle"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <FixedImage alists={effectsList} />
        </Form.Item>

        <Form.Item<FieldType>
          label="背景特效"
          name="background_effect"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <FixedImage alists={backgroundList} />
        </Form.Item>
        <Form.Item<FieldType>
          label="复制弹窗"
          name="copy_pop_up"
          extra={<>复制文本时进行弹窗提示</>}
        >
          <FixedImage alists={popUpList} />
        </Form.Item>

        <Form.Item>
          <h3 className="menu-header">美化</h3>
        </Form.Item>

        <Form.Item<FieldType>
          label="美化 - 美化滚动条"
          name="scrol"
          extra={
            <>
              让你的页面滚动条更美观，
              <a href="https://www.npc.ink/6217.html" target="_blank">
                详情
              </a>
            </>
          }
        >
          <FixedImage alists={scrollBarList} />
        </Form.Item>

        <Form.Item>
          <h3 className="menu-header">挂件</h3>
        </Form.Item>
        <Form.Item<FieldType>
          label="屏幕上的毛"
          name="screen_hair"
          valuePropName="checked"
          extra={
            <>
              在网页上添加一根毛发，蛮有趣的，
              <a href="https://mkblog.cn/2382/" target="_blank">
                详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="添加喜庆灯笼"
          name="lantern"
          valuePropName="checked"
          extra={<>特殊时间下会有特别的意义，移动端不展示，</>}
        >
          <Switch />
        </Form.Item>
        {formData.lantern && (
          <>
            <Form.Item<FieldType>
              label="左"
              name="lantern_left"
              extra={<>展示在左边</>}
            >
              <Input style={{ width: "20%" }} />
            </Form.Item>
            <Form.Item<FieldType>
              label="右"
              name="lantern_right"
              extra={<>展示在右边</>}
            >
              <Input style={{ width: "20%" }} />
            </Form.Item>
          </>
        )}

        <Form.Item<FieldType>
          label="上吊猫"
          name="page_back_top_cat"
          valuePropName="checked"
          extra={"添加一个可爱的猫猫，点击即可返回页面顶部"}
        >
          <Switch />
        </Form.Item>
        {formData.page_back_top_cat !== false && (
          <Form.Item<FieldType>
            label="猫猫距离右边"
            name="page_back_top_cat_right"
            extra={"右边距离"}
          >
            <InputNumber addonAfter={"px"} style={{ width: "120px" }} />
          </Form.Item>
        )}
        <Form.Item<FieldType>
          label="像素小鸡"
          name="pixel_chicken"
          valuePropName="checked"
          extra={
            <>
              页脚添加会动的像素小鸡和蘑菇，挺可爱的，移动端不显示。
              <Preview title="像素小鸡" img={PixelChicken} />
            </>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="已读完的书"
          name="past_books"
          valuePropName="checked"
          extra={
            <>
              页脚添加，统计您撰写的文章总字数，相当于那本书。
              <a href="https://www.npc.ink/276901.html" target="_blank">
                详细信息
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

//准备特效
import Diffuse from "@/assets/page/feature/effects/爆炸烟花.png";
import CircleFireworks from "@/assets/page/feature/effects/圆圈烟花.png";
import ScatteredFireworks from "@/assets/page/feature/effects/四散烟花.png";
import Text from "@/assets/page/feature/effects/随机文字.png";
import Number from "@/assets/page/feature/effects/随机数字.png";
import Love from "@/assets/page/feature/effects/七彩爱心.png";
import LoveWhirl from "@/assets/page/feature/effects/爱心回旋.png";
import StarTrail from "@/assets/page/feature/effects/星星拖尾.png";

const effectsList = [
  { value: "diffuse", label: Diffuse, title: "爆炸烟花" },
  { value: "circleFireworks", label: CircleFireworks, title: "圆圈烟花" },
  {
    value: "scatteredFireworks",
    label: ScatteredFireworks,
    title: "四散烟花",
  },
  { value: "text", label: Text, title: "随机文字" },
  { value: "number", label: Number, title: "随机数字" },
  { value: "love", label: Love, title: "七彩爱心" },
  { value: "loveWhirl", label: LoveWhirl, title: "爱心回旋" },
  { value: "starTrail", label: StarTrail, title: "星星拖尾" },
];

//背景特效
import Star from "@/assets/page/feature/backgroundEffect/漂浮星星.png";
import Sakura from "@/assets/page/feature/backgroundEffect/樱花.png";
import Coupling from "@/assets/page/feature/backgroundEffect/细线联结.png";
const backgroundList = [
  { value: "star", label: Star, title: "漂浮星星" },
  { value: "sakura", label: Sakura, title: "樱花效果" },
  { value: "coupling", label: Coupling, title: "细线联结" },
  { value: "drip_ink", label: Coupling, title: "滴墨水" },
  { value: "sliding_ribbon", label: Coupling, title: "流动彩带" },
  { value: "random_ribbon", label: Coupling, title: "随机彩带" },
];

//滚动条
import DiffuseBar from "@/assets/page/feature/scrollBar/默认.png";
import Color from "@/assets/page/feature/scrollBar/彩条.png";
const scrollBarList = [
  { value: "default", label: DiffuseBar, title: "默认" },
  { value: "color", label: Color, title: "彩条" },
];

//弹窗
import Concise from "@/assets/page/feature/popUp/原生弹窗.png";
import Sweetalert from "@/assets/page/feature/popUp/通用圆角.png";
const popUpList = [
  { value: "concise", label: Concise, title: "原生弹窗" },
  { value: "sweetalert", label: Sweetalert, title: "通用圆角" },
];

export default App;
