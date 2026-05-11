//时间输入框
//时间段
//["2024-05-01 12:00:00","2024-05-09 12:00:00"]
import { DatePicker } from "antd";
import dayjs from "dayjs";
const TimePeriod: React.FC = (props: any) => {
  //准备时间组件
  const { RangePicker } = DatePicker;

  //时间格式
  //const dateFormat = "YYYY-MM-DD HH:mm:ss";
  const dateFormat = "YYYY-MM-DD HH:mm";

  // 获取当前时间并格式化
  const currentTime = dayjs().format(dateFormat);

  // 计算1天后的时间并格式化
  const nextDay = dayjs().add(1, "day").format(dateFormat);

  //触发
  const onChange = (_value: any, dateString: any) => {
    //console.log("Selected Time: ", value);
    //console.log("Formatted Selected Time: ", dateString);
    //格式化时间
    const data = dateString.map((item: any) =>
      dayjs(item).format("YYYY-MM-DD HH:mm")
    );
    //console.log(data);
    props.onChange(data);
  };
  return (
    <>
      <RangePicker
        showTime={{ format: dateFormat }}
        format="YYYY-MM-DD HH:mm"
        onChange={onChange}
        // 注：defaultValue 可考虑通过 props 传入默认工厂函数抽离
        defaultValue={[
          dayjs(props.value[0] ?? currentTime, dateFormat),
          dayjs(props.value[1] ?? nextDay, dateFormat),
        ]}
      />
    </>
  );
};

export default TimePeriod;
