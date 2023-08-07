//默认变量
//准备布尔值
const boo: boolean = import.meta.env.VITE_BOOLEAN === true;
const App = {
  option: {
    name: import.meta.env.VITE_OPTION_NAME,
    age: parseInt(import.meta.env.VITE_OPTION_AGE),
    handle: import.meta.env.VITE_OPTION_HANDLE === "true",
  },
  //优化
  optimize: {
    //站点
    site: {
      //禁止转义
      no_escape: boo,
      //关键词自动添加链接
      add_inks: boo,
    },
    medium: {
      img_add_tag: boo,
      no_auto_size: boo,
      medium_add_svg: boo,
      upload_auto_name: "false",
    },
  },
};

export default App;
