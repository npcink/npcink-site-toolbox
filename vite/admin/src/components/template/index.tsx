import Static from "@/components/template/static";
import Trends from "@/components/template/trends";
const App: React.FC = () => {
  return (
    <>
      <div className="describe">启用对应模板后，在页面中可选择对应模板，部分模板提供选项</div>
      <Static />
      <Trends />
    </>
  );
};

export default App;