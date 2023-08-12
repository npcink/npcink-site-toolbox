type Data = {
  title: string;//标题
  num: number;//数量
  unit: string;//单位
  icon: string;//图标
};

const App = ({ data }: { data: Data }) => {
  return (
    <>
      <div className="box">
        <span>{data.title}</span>
        <div className="child">
          <p>
            <span>{data.num}</span>
            {data.unit}
          </p>
          <span className={data.icon}>图</span>
        </div>
      </div>
    </>
  );
};
export default App;
