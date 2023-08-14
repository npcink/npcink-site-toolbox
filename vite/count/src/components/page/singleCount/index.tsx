//作者发文统计页面
import { useContext } from "react";
import DataContext from "@/components/tool/dataContext";
import Basic from "@/components/page/singleCount/basicData";
import Today from "@/components/page/singleCount/today";
import Moon from "@/components/page/singleCount/moon";
function App() {
  //拿到值
  const optionObj = useContext(DataContext);
  const state = optionObj.single ? true : false;

  return (
    <>
      {/**
       * 若传值则展示，
       */}
      {state && (
        <>
          <h3>文章统计</h3>
          <hr />
          <div className="single-box">
            <div className="left">
              <Today />
            </div>
            <div className="right">
              <Basic />
            </div>
          </div>
          <h3>月度</h3>
          <Moon />
        </>
      )}
    </>
  );
}

export default App;
