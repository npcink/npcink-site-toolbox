//地图编辑表格
import React, { useContext, useEffect, useRef, useState } from "react";
import type { GetRef, InputRef } from "antd";
import { Button, Form, Input, Popconfirm, Table } from "antd";

type FormInstance<T> = GetRef<typeof Form<T>>;

const EditableContext = React.createContext<FormInstance<any> | null>(null);

interface Item {
  key: string;
  name: string;
  longitude: number;
  latitude: number;
}

interface EditableRowProps {
  index: number;
}

const EditableRow: React.FC<EditableRowProps> = ({ index, ...props }) => {
  const [form] = Form.useForm();
  return (
    <Form form={form} component={false}>
      <EditableContext.Provider value={form}>
        <tr {...props} />
      </EditableContext.Provider>
    </Form>
  );
};

interface EditableCellProps {
  title: React.ReactNode;
  editable: boolean;
  dataIndex: keyof Item;
  record: Item;
  handleSave: (record: Item) => void;
}

const EditableCell: React.FC<React.PropsWithChildren<EditableCellProps>> = ({
  title,
  editable,
  children,
  dataIndex,
  record,
  handleSave,
  ...restProps
}) => {
  const [editing, setEditing] = useState(false);
  const inputRef = useRef<InputRef>(null);
  const form = useContext(EditableContext)!;

  useEffect(() => {
    if (editing) {
      inputRef.current?.focus();
    }
  }, [editing]);

  const toggleEdit = () => {
    setEditing(!editing);
    form.setFieldsValue({ [dataIndex]: record[dataIndex] });
  };

  const save = async () => {
    try {
      const values = await form.validateFields();

      toggleEdit();
      handleSave({ ...record, ...values });
    } catch (errInfo) {
      console.log("Save failed:", errInfo);
    }
  };

  let childNode = children;

  if (editable) {
    childNode = editing ? (
      <Form.Item
        style={{ margin: 0 }}
        name={dataIndex}
        rules={[
          {
            required: true,
            message: `${title} is required.`,
          },
        ]}
      >
        <Input ref={inputRef} onPressEnter={save} onBlur={save} />
      </Form.Item>
    ) : (
      <div
        className="editable-cell-value-wrap"
        style={{ paddingRight: 24 }}
        onClick={toggleEdit}
      >
        {children}
      </div>
    );
  }

  return <td {...restProps}>{childNode}</td>;
};

type EditableTableProps = Parameters<typeof Table>[0];

interface DataType {
  key: React.Key;
  name: string;
  longitude: number;
  latitude: number;
}

type ColumnTypes = Exclude<EditableTableProps["columns"], undefined>;

//初始数据
//准备足迹类型
interface MarkersType {
  latLng: number[];
  name: string;
}

//转化方法 地图数据转表格数据
const convertMarkers = (markers: MarkersType[]) => {
  return markers.map((marker, index) => ({
    key: index + 1,
    name: marker.name,
    longitude: marker.latLng[1], // 经度在数组的第二个位置
    latitude: marker.latLng[0], // 纬度在数组的第一个位置
  }));
};

//转换方法 - 表格数据转地图数据
const convertBackToOriginal = (convertedMarkers: DataType[]) => {
  return convertedMarkers.map((marker) => ({
    latLng: [marker.latitude, marker.longitude], // 将 latitude 和 longitude 组成 latLng 数组
    name: marker.name,
  }));
};

const App: React.FC = (props: any) => {
  //const markers = [
  //  // 足迹位置
  //  {
  //    latLng: [31.4, 121.48],
  //    name: "上海",
  //  },
  //  {
  //    latLng: [39.09, 117.2],
  //    name: "天津",
  //  },
  //];
  const markers = props.value;

  //准备默认表格数据
  const convertedMarkers = convertMarkers(markers);

  //准备默认值
  const [dataSource, setDataSource] = useState<DataType[]>(convertedMarkers);

  //添加数据的序号
  const [count, setCount] = useState(markers.length + 1);

  //删除数据
  const handleDelete = (key: React.Key) => {
    const newData = dataSource.filter((item) => item.key !== key);
    setDataSource(newData);

    //传出数据
    const data = convertBackToOriginal(newData);
    props.onChange(data);
  };

  //准备表头
  const defaultColumns: (ColumnTypes[number] & {
    editable?: boolean;
    dataIndex: string;
  })[] = [
    {
      title: "序号",
      dataIndex: "key",
      width: "20%",
      render: (_text, _record, index) => <p>{index + 1}</p>, // 显示序号，index 是从 0 开始的
    },
    {
      title: "地区",
      dataIndex: "name",
      width: "20%",
      editable: true,
    },
    {
      title: "经度",
      dataIndex: "longitude",
      width: "20%",
      editable: true,
      render: (text) => <p>{text}</p>,
    },
    {
      title: "纬度",
      width: "20%",
      dataIndex: "latitude",
      editable: true,
      render: (text) => <p>{text}</p>,
    },

    {
      title: "操作",
      dataIndex: "operation",
      render: (_, record) =>
        dataSource.length >= 1 ? (
          <Popconfirm
            title="确定要删除吗?"
            onConfirm={() => handleDelete((record as Item).key)}
          >
            <a>删除</a>
          </Popconfirm>
        ) : null,
    },
  ];

  //添加新足迹
  const handleAdd = () => {
    const newData: DataType = {
      key: count,
      name: `新地方 ${count}`,
      longitude: 114.06,
      latitude: 22.54,
    };
    setDataSource([...dataSource, newData]);
    setCount(count + 1); //序号加一
  };

  //实现表格数据的动态更新和展示，提升了用户交互的实时性和友好性
  const handleSave = (row: DataType) => {
    const newData = [...dataSource];
    const index = newData.findIndex((item) => row.key === item.key);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });
    setDataSource(newData);

    //传出数据
    const data = convertBackToOriginal(newData);
    props.onChange(data);
  };

  const components = {
    body: {
      row: EditableRow,
      cell: EditableCell,
    },
  };

  //动态生成可编辑列配置
  const columns = defaultColumns.map((col) => {
    if (!col.editable) {
      return col;
    }
    return {
      ...col,
      onCell: (record: DataType) => ({
        record,
        editable: col.editable,
        dataIndex: col.dataIndex,
        title: col.title,
        handleSave,
      }),
    };
  });

  //打印当前数组内容
  //const printData = () => {
  //  const data = convertBackToOriginal(dataSource);
  //  console.log(data);
  //};

  return (
    <>
      <Button onClick={handleAdd} type="primary" style={{ marginBottom: 16 }}>
        添加
      </Button>
      {
        //<Button onClick={printData}>打印</Button>
      }

      <Table
        components={components} //覆盖默认的 table 元素
        rowClassName={() => "editable-row"} //表格行的类名
        dataSource={dataSource} //数据数组
        columns={columns as ColumnTypes} //表格列的配置描述
        bordered
        size="small"
        style={{ width: 550 }}
      />
    </>
  );
};

export default App;
