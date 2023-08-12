
import Count from "../block/count";

const datas = [
  {
    title: "待发货",
    num: 10,
    unit: "个",
    icon: "class",
  },
  {
    title: "总销售额",
    num: 10,
    unit: "个",
    icon: "class",
  },

  {
    title: "总订单",
    num: 10,
    unit: "个",
    icon: "class",
  },
  {
    title: "总退款",
    num: 10,
    unit: "个",
    icon: "class",
  },
  {
    title: "总退款订单",
    num: 10,
    unit: "个",
    icon: "class",
  },
];
const App: React.FC = () => (
  <>
    <div className="count-box">
      {datas.map((item, index) => (
        <Count key={index} data={item} />
      ))}
    </div>
  </>
);

export default App;
