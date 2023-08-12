import "./App.css";
//打包前注释
import "./load-styles.css";
import Tab from "./components/tab";
import Count from "./components/count";
import CountSingle from "./components/count_single";

function App() {
  return (
    <>
      <h3>销售统计</h3>
      <hr />
      <Tab />
      <h3>周数据预览</h3>
      <Count />
      <hr />
      <h3>发文统计</h3>
      <CountSingle />
    </>
  );
}

export default App;
