export const SinglePublishToday = {
  width: 600,
  height: 300,
  title: "统计",
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
  title: "月度统计",
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
  countData: {
    single: {
      count: SingleCount,
      today: SinglePublishToday,
      month: SinglePublishMonth,
    },
  },
};
export default App;