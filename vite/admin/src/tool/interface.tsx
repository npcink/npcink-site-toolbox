//准备对象类型

//准备类型
export type DataLocal = {
  optimize: {
    site: OptimizeSite;
    medium: OptimizeMedium;
    comment: OptimizeComment;
    secure: OptimizeSecure;
    other: OptimizeOther;
  };
  //个性化
  style: {
    page: StylePage;
  };
  //权限
  authority: {
    //禁用
    disable: AuthorityDisable;
    auxiliary: AuthorityAuxiliary;
  };
};



//优化 站点
export type OptimizeSite = {
  no_escape: boolean; //禁止转义
  add_inks: boolean; //关键词自动添加链接
  modify_login_link: boolean; //登录页LOGO改首页链接
  remove_langue: boolean; //移除登录页语言选择框
};

//优化 媒体
export type OptimizeMedium = {
  img_add_tag: boolean;
  no_auto_size: boolean;
  medium_add_svg: boolean;
  upload_auto_name: string;
};

//优化 评论
export type OptimizeComment = {
  interval: boolean; //两次评论间隔
  interval_time: number; //间隔时间
  words_number: boolean; //是否开启字数控制
  words_number_min: number; //最少评论字数
  words_number_max: number; //最多评论字数
  english: boolean; //禁止纯英文评论
  japanese: boolean; //禁止纯日文评论
  only: boolean; //单篇文章仅限评论一次
};

//优化 安全
export type OptimizeSecure = {
  replace_login_error: boolean; //替换登录报错信息
  modify_comment_user: boolean; //修改评论区管理员样式ID
  remove_RSS_version: boolean; //从RSS源中删除WordPress版本信息
};

//优化 其他
export type OptimizeOther = {
  //筛选
  add_user: boolean; //作者筛选
  add_time: boolean; //时间筛选
  //显示ID
  show_id: boolean; //列表显示ID
};

//个性化
export type StylePage = {
  particle: boolean; //粒子特效
  color_tag: boolean; //彩色标签云特效
  comment_emote: boolean; //评论区表情包特效
  custom_login_page: boolean; //自定义登录页
  background_left: string; //左下角颜色
  background_right: string; //右上角颜色
  logo_size: number; //LOGO尺寸
  top_logo: string; //顶部LOGO
  background_img: string; //文字背景图
};

//权限 禁用
export type AuthorityDisable = {
  renew: boolean; //禁用自动更新
  no_login_img: boolean; //未登录模糊图片
};

//权限 辅助
export type AuthorityAuxiliary = {
  single_count: boolean; //文章统计
  b2_count: boolean; //B2商城统计
  no_malice_key: boolean; //拒绝恶意关键词
  malice_keu_content: string; //恶意关键词内容
  login_code: string; //登录验证码
  tecent_id: string; //腾讯ID
  tecent_key: string; //腾讯秘钥
};
