//单柱状图
import { useRef, useEffect } from "react";
import * as echarts from "echarts/core";
import {
  GridComponent,
  TitleComponent,
  TooltipComponent,
} from "echarts/components";
import { BarChart } from "echarts/charts";
import { CanvasRenderer } from "echarts/renderers";
import { Column } from "@/components/tool/interface";
echarts.use([
  GridComponent,
  BarChart,
  CanvasRenderer,
  TitleComponent,
  TooltipComponent,
]);

const App = ({ data }: { data: Column }) => {
  //准备数据
  const option = {
    title: {
      text: data.title,
    },
    tooltip: {
      valueFormatter: (value: number) => value.toFixed(0) + "个",
    },
    xAxis: {
      type: "category",
      data: data.x,
    },
    yAxis: {
      type: "value",
    },
    series: [
      {
        name: data.s.title,
        data: data.s.data,
        type: "bar",
        showBackground: true,
        backgroundStyle: {
          color: "rgba(180, 180, 180, 0.2)",
        },
        label: {
          show: true,
          position: "insideTop", //在上方显示
          textStyle: {
            //数值样式
            color: "#fff",
            fontSize: 12,
            fontWeight: "bold",
          },
        },
      },
    ],
  };
  //准备节点
  const chartRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    //找节点
    const myChart = echarts.init(chartRef.current);

    //做数据
    myChart.setOption(option);

    // 清除图表实例
    return () => {
      myChart.dispose();
    };
  }, []);

  return <div ref={chartRef} style={{ width: "600px", height: "300px" }}></div>;
};

export default App;
