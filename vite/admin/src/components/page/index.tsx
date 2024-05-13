//聚合
import Comment from "@/components/page/comment";
import Feature from "@/components/page/feature";
import Function from "@/components/page/function";
const App: React.FC = () => {
  return (
    <>
      <Function />
      {/**功能 */}
      <Comment /> {/**评论 */}
      <Feature /> {/**外观 */}
    </>
  );
};

export default App;
