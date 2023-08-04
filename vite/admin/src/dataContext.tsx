//存值

import { createContext } from "react";
//准备的默认值
const dataObject = {
  site: {
    //禁止转义
    no_escape: false,
    //关键词自动添加链接
    automatically_add_inks_keywords: true,
  },
  //筛选
  screen: {
    Article_Menu_Author: false,
  },
  //显示ID
  show_id: {
    all: false,
  },
};
const DataContext = createContext(dataObject);

export default DataContext;
