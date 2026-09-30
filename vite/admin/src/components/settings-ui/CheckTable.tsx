import React from "react";
import { Table } from "antd";

interface CheckTableColumn<RecordType> {
  title: string;
  dataIndex?: string;
  key: string;
  width?: number;
  render?: (value: unknown, record: RecordType, index: number) => React.ReactNode;
}

interface CheckTableProps<RecordType> {
  columns: CheckTableColumn<RecordType>[];
  dataSource: RecordType[];
  rowKey?: string;
  loading?: boolean;
  className?: string;
}

function CheckTable<RecordType>({
  columns,
  dataSource,
  rowKey = "key",
  loading,
  className,
}: CheckTableProps<RecordType>) {
  return (
    <div className={`mabox-check-table ${className || ""}`}>
      <Table
        columns={columns}
        dataSource={dataSource}
        rowKey={rowKey}
        loading={loading}
        pagination={false}
        size="small"
        scroll={{ x: "max-content" }}
      />
    </div>
  );
}

export default CheckTable;