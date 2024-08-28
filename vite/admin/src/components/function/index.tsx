//权限管理
import Auxiliary from "@/components/function/auxiliary";
import B2 from "@/components/function/b2";
import Wx_xcx_link from "@/components/function/wx_xcx_link";
import DownDatabase from "@/components/function/down_database";
import Seo from "@/components/function/seo";
import Tips from "@/components/function/tips";
const App: React.FC = () => {
  return (
    <>
      <Tips /> {/**提示信息 */}
      <Seo />
      {/**下载指定数据库表内容 */}
      <DownDatabase />
      {/**辅助功能 */}
      <Auxiliary />
      {/**微信小程序链接 */}
      <Wx_xcx_link />
      {/**B2功能 */}
      <B2 />
    </>
  );
};

export default App;
