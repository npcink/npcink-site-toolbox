//HTML 输入框
import { useState } from "react";
import { Input } from "antd";
//反转义

const unescapeHtmlTagsInString = (escapedString: string) => {
  const tempElement = document.createElement("textarea");

  tempElement.innerHTML = escapedString;

  return tempElement.value;
};

const TextAreaHtml: React.FC = (props: any) => {
  const { TextArea } = Input;

  const [textAreaValue, setTextAreaValue] = useState(
    unescapeHtmlTagsInString(props.value)
  );

  const handleTextAreaChange = (e: any) => {
    //对字符串进行转化

    const data = e.target.value.replace(/</g, "&lt;").replace(/>/g, "&gt;");

    //console.log(data);

    setTextAreaValue(e.target.value); // 更新 textAreaValue 的值

    props.onChange(data); //传出值
  };

  return (
    <>
      <TextArea
        rows={4}
        value={textAreaValue}
        onChange={handleTextAreaChange}
      />
    </>
  );
};

export default TextAreaHtml;
