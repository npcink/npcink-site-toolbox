//多人版
import { useRef, useEffect } from "react";
import * as echarts from "echarts/core";
import {
  DatasetComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
} from "echarts/components";
import { BarChart } from "echarts/charts";
import { CanvasRenderer } from "echarts/renderers";
import { ColumnMore } from "@/components/tool/interface";
echarts.use([
  DatasetComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
  BarChart,
  CanvasRenderer,
]);

const App = ({ data }: { data: ColumnMore }) => {
  //准备数据
  //获取type数量
  const typeNumber = () => {
    const num = data.dataset[0].length - 1;
    return Array.from({ length: num }, () => ({ type: "bar" }));
  };

  const option = {
    title: {
      text: data.title,
    },
    tooltip: {
      valueFormatter: (value: number) => value.toFixed(0) + "个",
    },
    legend: {},
    dataset: {
      source: data.dataset,
    },
    xAxis: { type: "category" },
    yAxis: {},
    //声明几个条形系列，每个都将被映射
    //默认情况下为dataset.source的列。
    series: typeNumber(),
  };

  //准备节点
  const chartRefs = useRef<HTMLDivElement>(null);

  useEffect(() => {
    //找节点
    const myChart = echarts.init(chartRefs.current);

    //做数据
    myChart.setOption(option);

    // 清除图表实例
    return () => {
      myChart.dispose();
    };
  }, []);

  return (
    <div
      ref={chartRefs}
      style={{
        width: `${data.width ?? 600}px`,
        height: `${data.height ?? 300}px`,
      }}
    ></div>
  );
};

export default App;
