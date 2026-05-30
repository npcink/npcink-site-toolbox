import React from "react";
import { Table } from "antd";

interface CheckTableColumn {
  title: string;
  dataIndex?: string;
  key: string;
  width?: number;
  render?: (value: any, record: any, index: number) => React.ReactNode;
}

interface CheckTableProps {
  columns: CheckTableColumn[];
  dataSource: any[];
  rowKey?: string;
  loading?: boolean;
  className?: string;
}

const CheckTable: React.FC<CheckTableProps> = ({
  columns,
  dataSource,
  rowKey = "key",
  loading,
  className,
}) => {
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
};

export default CheckTable;