import "./App.css";
//打包前注释
//import "./load-styles.css";
import B2Shop from "./components/b2_shop";
import SingleCount from "./components/single_count";

function App() {
  return (
    <>
      {/**
       * 销售统计
       */}
    
      <B2Shop />
      {/**
       * 周数据预览
       */}

      <SingleCount />
      
    </>
  );
}

export default App;
