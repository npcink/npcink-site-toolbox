import React, { useState } from "react";
import { Segmented } from "antd";
import ProviderConfig from "@/components/ai_review/provider_config";
import AuditLog from "@/components/ai_review/audit_log";

const App: React.FC = () => {
  const [activeSection, setActiveSection] = useState<"config" | "logs">("config");

  return (
    <>
      <Segmented
        options={[
          { label: "审核配置", value: "config" },
          { label: "审核日志", value: "logs" },
        ]}
        value={activeSection}
        onChange={(val) => setActiveSection(val as "config" | "logs")}
        style={{ marginBottom: 16 }}
      />

      {activeSection === "config" && <ProviderConfig />}
      {activeSection === "logs" && <AuditLog />}
    </>
  );
};

export default App;
