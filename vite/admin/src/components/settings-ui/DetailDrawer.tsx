import React from "react";
import { Drawer, Typography } from "antd";

const { Paragraph } = Typography;

interface DetailDrawerProps {
  title: string;
  visible: boolean;
  onClose: () => void;
  description?: string;
  children?: React.ReactNode;
  width?: number;
}

const DetailDrawer: React.FC<DetailDrawerProps> = ({
  title,
  visible,
  onClose,
  description,
  children,
  width = 480,
}) => {
  return (
    <Drawer
      title={title}
      placement="right"
      onClose={onClose}
      open={visible}
      width={width}
      rootClassName="mabox-detail-drawer"
      styles={{ body: { paddingTop: 12 } }}
    >
      {description && (
        <Paragraph style={{ color: "#666", marginBottom: 16 }}>
          {description}
        </Paragraph>
      )}
      {children}
    </Drawer>
  );
};

export default DetailDrawer;
