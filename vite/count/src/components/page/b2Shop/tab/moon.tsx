import { useContext } from "react";
import Count from "@/components/block/count";
import { ShopMoon } from "@/components/tool/defaultVar";
import DataContext from "@/components/tool/dataContext";
const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { shop: {} };

  //给默认值
  const Data = optionObj.shop?.moon || ShopMoon;

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
