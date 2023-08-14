//发文统计 左表右块
import { useContext } from "react";
import ColumnMore from "@/components/block/column_more";
import Count from "@/components/block/count";
import { SinglePublish, SingleCount } from "../../tool/defaultVar";
import DataContext from "../../tool/dataContext";

const App = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { single: {} };

  //表格
  const DataPublish = optionObj.single?.today || SinglePublish;

  //列表
  const DataCount = optionObj.single?.count || SingleCount;
  return (
    <>
      <div className="single-box">
        <div className="left">
          <ColumnMore data={DataPublish} />
        </div>
        <div className="right">
          {DataCount.map((item, index) => (
            <Count key={index} data={item} />
          ))}
        </div>
      </div>
    </>
  );
};
export default App;
