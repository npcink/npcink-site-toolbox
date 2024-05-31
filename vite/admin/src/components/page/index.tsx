//聚合
import Comment from "@/components/page/comment";
import Feature from "@/components/page/feature";
import Function from "@/components/page/function";
import Jurisdiction from "@/components/page/jurisdiction";
const App: React.FC = () => {
  return (
    <>
      <Jurisdiction />
      {/**权限 */}
      <Function />
      {/**功能 */}
      <Comment /> {/**评论 */}
      <Feature /> {/**外观 */}
    </>
  );
};

export default App;
