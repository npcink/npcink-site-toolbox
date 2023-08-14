export type DataLocal = {
  shop: {
    today: Array<Count>;
    moon: Array<Count>;
    form: Array<Column>;
  };
  single: {
    count: Array<Count>;
    today: ColumnMore;
    moon: ColumnMore;
  };
  //count: Count; //小模块
  //column: Column; //单柱状图
  //ColumnMore: ColumnMore; //多柱状图
};

//模块
export type Count = {
  title: string; //标题
  num: number; //数量
  unit: string; //单位
  icon: string; //图标
};

//单柱状图
export type Column = {
  
  title: string; //标题
  x: Array<string>; //横轴数据
  s: {
    title: string; //提示标题
    data: Array<number>; //数据
  };
};

//多柱状图
export type ColumnMore = {
  width:number;//表格宽
  height:number;//表格高
  title: string; //标题
  dataset: Array<Array<string | number>>; //数据
};
