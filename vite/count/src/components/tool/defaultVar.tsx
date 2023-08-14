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
    num: 10.123456,
    unit: "￥",
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
    unit: "￥",
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
export const ShopMonth = [
  {
    title: "总销售额",
    num: 10,
    unit: "￥",
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
    unit: "￥",
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
    ["user", "大大怪", "小小怪", "007"],
    ["01", 43, 85, 93],
    ["02", 83, 73, 55],
    ["03", 86, 65, 82],
    ["04", 72, 53, 39],
  ],
};

export const SinglePublishMonth = {
  width: 1200,
  height: 300,
  title: "月度统计", //标题
  dataset: [
    ["user", "大大怪", "小小怪", "007"],
    ["01", 43, 85, 93],
    ["02", 83, 73, 55],
    ["03", 86, 65, 82],
    ["04", 72, 53, 39],
    ["05", 12, 33, 59],
    ["06", 22, 23, 69],
    ["07", 32, 13, 79],
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
    month: ShopMonth, //本月销售统计信息
    form: ShopForm, //最近7天销售统计信息
  },
  single: {
    count: SingleCount, //文章统计
    today: SinglePublishToday, //今日发文统计
    month: SinglePublishMonth, //月度发文统计
  },
};
export default App;
