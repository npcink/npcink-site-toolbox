//页面 - 外观优化
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, InputNumber } from "antd";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFeature } from "@/tool/interface";
import FixedImage from "@/basic/fixedImage";
import FeatureSwitch from "@/basic/feature-switch";
import PixelChicken from "@/assets/page/feature/像素小鸡.png";
import Preview from "@/basic/preview";
import { checkRiskyFeature } from "@/tool/riskyFeature.tsx";

type FieldType = PageFeature;

const fromConfig = AntConfig.from;

const RISKY_FIELDS: Record<string, string> = {
  particle: "page-feature-particle",
  background_effect: "page-feature-background_effect",
  site_grey: "page-feature-site_grey",
  lantern: "page-feature-lantern",
};

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  let publicData = optionData.page?.feature || defaultVarOption.page.feature;
  const [formData, setFormData] = useState(publicData || {});

  const applyChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    const fieldKey = Object.keys(changedValues)[0];
    const featureId = RISKY_FIELDS[fieldKey];
    if (featureId) {
      const newValue = changedValues[fieldKey as keyof FieldType];
      const shouldProceed = checkRiskyFeature(featureId, newValue, () => {
        applyChange(changedValues);
      });
      if (!shouldProceed) {
        return;
      }
    }
    applyChange(changedValues);
  };

  useEffect(() => {
    updateOption("page", "feature", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="aspect"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
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
          id="page-feature-title"
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
          <FeatureSwitch featureId="page-feature-title" />
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
          id="page-feature-top_loading"
          label="顶部加载进度条"
          name="top_loading"
          valuePropName="checked"
          extra={<>火狐浏览器不显示</>}
        >
          <FeatureSwitch featureId="page-feature-top_loading" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-feature-reading_progress"
          label="阅读进度条"
          name="reading_progress"
          valuePropName="checked"
          extra={"文章页面顶部显示阅读进度指示器，仅文章页展示"}
        >
          <FeatureSwitch featureId="page-feature-reading_progress" />
        </Form.Item>
        {formData.reading_progress && (
          <>
            <Form.Item<FieldType>
              label="进度条颜色"
              name="reading_progress_color"
            >
              <Input style={{ width: "30%" }} placeholder="#1677ff" />
            </Form.Item>
            <Form.Item<FieldType>
              label="进度条高度"
              name="reading_progress_height"
              extra={"单位: 像素"}
            >
              <InputNumber addonAfter={"px"} style={{ width: "120px" }} min={1} max={10} />
            </Form.Item>
          </>
        )}
        <Form.Item<FieldType>
          id="page-feature-site_grey"
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
          <FeatureSwitch featureId="page-feature-site_grey" />
        </Form.Item>

        <Form.Item<FieldType>
          id="page-feature-page_scrolling"
          label="平滑滚动"
          name="page_scrolling"
          valuePropName="checked"
          extra={"让页面滚动起来更丝滑，部分浏览器不支持"}
        >
          <FeatureSwitch featureId="page-feature-page_scrolling" />
        </Form.Item>

        <Form.Item<FieldType>
          id="page-feature-particle"
          label="点击特效"
          name="particle"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <FixedImage alists={effectsList} />
        </Form.Item>

        <Form.Item<FieldType>
          id="page-feature-background_effect"
          label="背景特效"
          name="background_effect"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <FixedImage alists={backgroundList} />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-feature-copy_pop_up"
          label="复制弹窗"
          name="copy_pop_up"
          extra={<>复制文本时进行弹窗提示</>}
        >
          <FixedImage alists={popUpList} />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-feature-bottom_effect"
          label="页底效果"
          name="bottom_effect"
          extra={"页面底部添加装饰效果，移动端不显示（待实现）"}
        >
          <FixedImage alists={bottomEffectList} />
        </Form.Item>

        <Form.Item>
          <h3 className="menu-header">美化</h3>
        </Form.Item>

        <Form.Item<FieldType>
          id="page-feature-scrol"
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
          id="page-feature-screen_hair"
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
          <FeatureSwitch featureId="page-feature-screen_hair" />
        </Form.Item>

        <Form.Item<FieldType>
          id="page-feature-lantern"
          label="添加喜庆灯笼"
          name="lantern"
          valuePropName="checked"
          extra={<>特殊时间下会有特别的意义，移动端不展示，</>}
        >
          <FeatureSwitch featureId="page-feature-lantern" />
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
          id="page-feature-pixel_chicken"
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
          <FeatureSwitch featureId="page-feature-pixel_chicken" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-feature-past_books"
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
          <FeatureSwitch featureId="page-feature-past_books" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-feature-go_top"
          label="返回顶部"
          name="go_top"
          extra={<>屏幕底部右侧，添加返回顶部挂件</>}
        >
          <FixedImage alists={goTopList} />
        </Form.Item>
        {formData.go_top == "cord_cat" && (
          <Form.Item<FieldType>
            label="猫猫距离右边"
            name="page_back_top_cat_right"
            extra={"右边距离"}
          >
            <InputNumber addonAfter={"px"} style={{ width: "120px" }} />
          </Form.Item>
        )}
      </Form>
    </>
  );
};

//点击特效
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
import Flowing_lines from "@/assets/page/feature/backgroundEffect/流动线条.png";
import Drip_ink from "@/assets/page/feature/backgroundEffect/滴墨水.png";
import Sliding_ribbon from "@/assets/page/feature/backgroundEffect/流动彩带.png";
import Random_ribbon from "@/assets/page/feature/backgroundEffect/随机彩带.png";
import Floating_sphere from "@/assets/page/feature/backgroundEffect/质感圆球.png";
const backgroundList = [
  { value: "star", label: Star, title: "漂浮星星" },
  { value: "sakura", label: Sakura, title: "樱花效果" },
  { value: "coupling", label: Coupling, title: "细线联结" },
  { value: "flowing_lines", label: Flowing_lines, title: "流动线条" },
  { value: "drip_ink", label: Drip_ink, title: "滴墨水" },
  { value: "sliding_ribbon", label: Sliding_ribbon, title: "流动彩带" },
  { value: "random_ribbon", label: Random_ribbon, title: "随机彩带" },
  { value: "floating_sphere", label: Floating_sphere, title: "质感圆球" },
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

//页底效果
import Fish from "@/assets/page/feature/bottom/鱼群.png";
const bottomEffectList = [{ value: "fish", label: Fish, title: "鱼群跃动" }];

//返回顶部
import Smooth_arrow from "@/assets/page/feature/go_top/平滑箭头.png";
import Peep_cat from "@/assets/page/feature/go_top/偷瞄猫猫.png";
import Cord_cat from "@/assets/page/feature/go_top/抓绳猫猫.png";
const goTopList = [
  { value: "smooth_arrow", label: Smooth_arrow, title: "平滑箭头" },
  { value: "peep_cat", label: Peep_cat, title: "偷瞄猫猫" },
  { value: "cord_cat", label: Cord_cat, title: "抓绳猫猫" },
];

export default App;
