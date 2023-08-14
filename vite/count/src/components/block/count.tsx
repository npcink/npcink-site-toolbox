import { Count } from "@/components/tool/interface";

const App = ({ data }: { data: Count }) => {
  return (
    <>
      <div className="box">
        <span>{data.title}</span>
        <div className="child">
          <p>
            <span>
              {data.num % 1 === 0
                ? data.num.toFixed(0)
                : parseFloat(data.num.toFixed(3))}
            </span>
            {data.unit}
          </p>
          <span className={data.icon}></span>
        </div>
      </div>
    </>
  );
};
export default App;
