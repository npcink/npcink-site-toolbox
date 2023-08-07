//准备对象类型

//准备类型
export type DataLocal = {
  option: FieldType;
  optimize: {
    site: OptimizeSite;
    medium: OptimizeMedium;
  };
};

type FieldType = {
  name?: string;
  age?: number;
  handle?: boolean;
};

//优化 站点
export type OptimizeSite = {
  //禁止转义
  no_escape: boolean;
  //关键词自动添加链接
  add_inks: boolean;
};

//优化 媒体
export type OptimizeMedium = {
  img_add_tag: boolean;
  no_auto_size: boolean;
  medium_add_svg: boolean;
  upload_auto_name: string;
};
