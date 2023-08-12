//展示表格
import Column from "./block/column";
const datas = [
  {
    title: "最近7天总销售额（已减退款额）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总销售额", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
  {
    title: "最近7天总销售订单（已减退款订单）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总销售订单", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
  {
    title: "最近7天总退款额（已减退款订单）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总退款订单", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
  {
    title: "最近7天总退款订单（已减退款订单）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总销售退款订单", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
];
const App = () => {
  return (
    <>
      <div className="form-box">
        {datas.map((item, index) => (
          <Column key={index} data={item} />
        ))}
      </div>
    </>
  );
};
export default App;
