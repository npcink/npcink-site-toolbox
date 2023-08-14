//今天销售
export const ShopToday = [
  {
    title: "待发货",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-store",
  },
  {
    title: "总销售额",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-insert",
  },

  {
    title: "总订单",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-database-add",
  },
  {
    title: "总退款",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-remove",
  },
  {
    title: "总退款订单",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-database-remove",
  },
];

//月销售
export const ShopMoon = [
  {
    title: "总销售额",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-insert",
  },

  {
    title: "总订单",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-database-add",
  },
  {
    title: "总退款",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-remove",
  },
  {
    title: "总退款订单",
    num: 10,
    unit: "个",
    icon: "dashicons dashicons-database-remove",
  },
];

//销售表格
export const ShopForm = [
  {
    title: "最近7天总销售额（已减退款额）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总销售额", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
  {
    title: "最近7天总销售订单（已减退款订单）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总销售订单", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
  {
    title: "最近7天总退款额（已减退款订单）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总退款订单", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
  {
    title: "最近7天总退款订单（已减退款订单）", //标题
    x: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], //横轴数据
    s: {
      title: "总销售退款订单", //提示标题
      data: [120, 200, 150, 80, 70, 110, 130], //数据
    },
  },
];

//文章统计 每天发文数量
export const SinglePublishToday = {
  width: 600,
  height: 300,
  title: "统计", //标题
  dataset: [
    ["time", "2015", "2016", "2017"],
    ["Matcha Latte", 43.3, 85.8, 93.7],
    ["Milk Tea", 83.1, 73.4, 55.1],
    ["Cheese Cocoa", 86.4, 65.2, 82.5],
    ["Walnut Brownie", 72.4, 53.9, 39.1],
  ],
};

export const SinglePublishMoon = {
  width: 1200,
  height: 300,
  title: "月度统计", //标题
  dataset: [
    ["time", "2015", "2016", "2017"],
    ["Matcha Latte", 43.3, 85.8, 93.7],
    ["Milk Tea", 83.1, 73.4, 55.1],
    ["Cheese Cocoa", 86.4, 65.2, 82.5],
    ["Walnut Brownie", 72.4, 53.9, 39.1],
  ],
};

//文章统计
export const SingleCount = [
  {
    title: "今日发文",
    num: 10,
    unit: "篇",
    icon: "dashicons dashicons-text-page",
  },
  {
    title: "今日评论",
    num: 10,
    unit: "条",
    icon: "dashicons dashicons-format-status",
  },
  {
    title: "今日注册",
    num: 10,
    unit: "位",
    icon: "dashicons dashicons-universal-access",
  },
];

const App = {
  shop: {
    today: ShopToday, //今天的销售统计信息
    moon: ShopMoon, //本月销售统计信息
    form: ShopForm, //最近7天销售统计信息
  },
  single: {
    count: SingleCount, //文章统计
    today: SinglePublishToday, //今日发文统计
    moon: SinglePublishMoon, //月度发文统计
  },
};
export default App;
