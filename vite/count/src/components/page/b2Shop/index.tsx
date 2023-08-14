//B2销售统计页面
import { useContext } from "react";
import Tab from "@/components/page/b2Shop/tab/index";
import Count from "@/components/page/b2Shop/count";
import DataContext from "@/components/tool/dataContext";
function App() {
  //拿到值
  const optionObj = useContext(DataContext);
  const state = optionObj.shop ? true : false;
  return (
    <>
      {state && (
        <>
          <h3>销售统计</h3>
          
          <Tab />
          <h3>周数据预览</h3>
          <Count />
        </>
      )}
    </>
  );
}

export default App;
