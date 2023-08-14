import { useContext } from "react";
import Count from "@/components/block/count";
import { ShopToday } from "../../../tool/defaultVar";
import DataContext from "../../../tool/dataContext";

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { shop: {} };

  //给默认值
  const Data = optionObj.shop?.today || ShopToday;
  return (
    <>
      <div className="count-box">
        {Data.map((item, index) => (
          <Count key={index} data={item} />
        ))}
      </div>
    </>
  );
};

export default App;
