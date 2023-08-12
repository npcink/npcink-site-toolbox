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

echarts.use([
  DatasetComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
  BarChart,
  CanvasRenderer,
]);

type Data = {
  title: string; //标题
  dataset: Array<Array<string | number>>; //数据
};

const App = ({ data }: { data: Data }) => {
  //准备数据
  //获取type数量
  const typeNumber = () => {
    const num = data.dataset.length - 1;
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
    <div ref={chartRefs} style={{ width: "600px", height: "300px" }}></div>
  );
};

export default App;
