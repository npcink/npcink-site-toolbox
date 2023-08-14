import { Count } from "@/components/tool/interface";

const App = ({ data }: { data: Count }) => {
  return (
    <>
      <div className="box">
        <span>{data.title}</span>
        <div className="child">
          <p>
            <span>{data.num}</span>
            {data.unit}
          </p>
          <span className={data.icon}></span>
        </div>
      </div>
    </>
  );
};
export default App;
