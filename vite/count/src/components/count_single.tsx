//展示表格
import ColumnMore from "./block/column_more";
const data = {
  title: "统计", //标题
  dataset: [
    ["time", "2015", "2016", "2017"],
    ["Matcha Latte", 43.3, 85.8, 93.7],
    ["Milk Tea", 83.1, 73.4, 55.1],
    ["Cheese Cocoa", 86.4, 65.2, 82.5],
    ["Walnut Brownie", 72.4, 53.9, 39.1],
  ],
};
const App = () => {
  return (
    <>
      <div className="form-boxs">
        <ColumnMore data={data} />
      </div>
    </>
  );
};
export default App;
