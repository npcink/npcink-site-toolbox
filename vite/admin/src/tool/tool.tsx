import type { FormProps } from "antd";

type ColProps = FormProps["labelCol"];

interface AntFrom {
  from: {
    labelCol: ColProps;
    wrapperCol: ColProps;
    maxWidth: number;
  };
}

export const AntConfig: AntFrom = {
  from: {
    labelCol: { xs: 24, sm: 8, md: 6 },
    wrapperCol: { xs: 24, sm: 16, md: 18 },
    maxWidth: 900,
  },
};

//网址验证
export const validateLink = (_: any, value: string) => {
  const urlPattern =
    /^(https?):\/\/(?:www\.)?([a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*)(?:\/[^\s]*)?$/;
  if (!value || value.match(urlPattern)) {
    return Promise.resolve();
  }
  return Promise.reject("请输入有效的链接 URL");
};
