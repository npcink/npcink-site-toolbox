import { useCallback, useEffect, useMemo, useState } from "react";

import { diagnosticsApi } from "@/api";
import type { RuntimeFeatureModule, RuntimeFeatureStatus } from "@/tool/interface";
import { createAdminTargetUrl } from "@/tool/navigation";
import { diagnosticLabels, scopeLabels, tierLabels } from "./runtime-status-report";

import "./runtime-status.css";

type LoadState =
  | { status: "loading"; data: null }
  | { status: "success"; data: RuntimeFeatureStatus }
  | { status: "error"; data: null };

interface RuntimeStatusProps {
  onNavigate?: (view: string, itemId?: string) => void;
}

function isRuntimeFeatureStatus(value: unknown): value is RuntimeFeatureStatus {
  if (!value || typeof value !== "object") return false;
  const data = value as Partial<RuntimeFeatureStatus>;
  return Boolean(
    data.plugin &&
    typeof data.plugin.name === "string" &&
    typeof data.plugin.version === "string" &&
    data.environment &&
    typeof data.environment.wordpress_version === "string" &&
    typeof data.environment.php_version === "string" &&
    data.counts &&
    typeof data.counts.registered === "number" &&
    typeof data.counts.active === "number" &&
    typeof data.counts.always_loaded === "number" &&
    typeof data.counts.editor_tools === "number" &&
    Array.isArray(data.modules) &&
    data.modules.every(
      (module) =>
        typeof module.id === "string" &&
        typeof module.label === "string" &&
        typeof module.target_id === "string",
    ) &&
    Array.isArray(data.editor_tools) &&
    data.diagnostics &&
    typeof data.diagnostics.status === "string" &&
    typeof data.generated_at === "string"
  );
}

const RuntimeStatus = ({ onNavigate }: RuntimeStatusProps) => {
  const [state, setState] = useState<LoadState>({ status: "loading", data: null });

  const load = useCallback(async () => {
    setState({ status: "loading", data: null });
    try {
      const response = await diagnosticsApi.getFeatureStatus();
      if (!response?.success || !isRuntimeFeatureStatus(response.data)) {
        setState({ status: "error", data: null });
        return;
      }
      setState({ status: "success", data: response.data });
    } catch {
      setState({ status: "error", data: null });
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const groupedModules = useMemo(() => {
    if (state.status !== "success") return [];
    const groups = new Map<string, RuntimeFeatureModule[]>();
    state.data.modules.forEach((module) => {
      const modules = groups.get(module.category_label) || [];
      modules.push(module);
      groups.set(module.category_label, modules);
    });
    return Array.from(groups.entries());
  }, [state]);

  if (state.status === "loading") {
    return (
      <div className="mabox-runtime-state" role="status">
        <span className="mabox-view-state-spinner" aria-hidden="true" />
        <div><strong>正在读取运行状态</strong><span>只读取模块和环境事实，不会修改设置。</span></div>
      </div>
    );
  }

  if (state.status === "error") {
    return (
      <div className="mabox-runtime-state mabox-runtime-state--error" role="alert">
        <div><strong>运行状态暂时不可用</strong><span>读取失败，没有生成不完整的诊断信息。</span></div>
        <button type="button" className="button" onClick={() => void load()}>重新获取</button>
      </div>
    );
  }

  const { data } = state;

  return (
    <div className="mabox-runtime-status">
      <header className="mabox-runtime-status__header">
        <div>
          <h2>功能与运行状态</h2>
          <p>核对当前实际加载的模块、编辑器工具和基础运行环境；本页面只读，不会发送外部请求。</p>
        </div>
        <button type="button" className="button" onClick={() => void load()}>刷新</button>
      </header>

      <dl className="mabox-runtime-status__summary" aria-label="运行状态摘要">
        <div><dt>插件版本</dt><dd>{data.plugin.version || "未知"}</dd></div>
        <div><dt>运行模块</dt><dd>{data.counts.active} / {data.counts.registered}</dd></div>
        <div><dt>无需开关</dt><dd>{data.counts.always_loaded}</dd></div>
        <div><dt>编辑器工具</dt><dd>{data.counts.editor_tools}</dd></div>
      </dl>

      <section className="mabox-runtime-status__section" aria-labelledby="runtime-environment-heading">
        <div className="mabox-runtime-status__section-heading">
          <div>
            <h3 id="runtime-environment-heading">运行环境</h3>
            <p>仅显示影响插件运行的版本事实，不使用综合评分。</p>
          </div>
          <span className={`mabox-runtime-status__badge mabox-runtime-status__badge--${data.diagnostics.status}`}>
            {diagnosticLabels[data.diagnostics.status]}
          </span>
        </div>
        <dl className="mabox-runtime-status__facts">
          <div><dt>WordPress</dt><dd>{data.environment.wordpress_version}</dd></div>
          <div><dt>PHP</dt><dd>{data.environment.php_version}</dd></div>
          <div><dt>生成时间</dt><dd>{data.generated_at}</dd></div>
        </dl>
        <ul className="mabox-runtime-status__checks">
          {data.diagnostics.items.map((item) => (
            <li key={item.id}>
              <span className={`mabox-runtime-status__dot mabox-runtime-status__dot--${item.status}`} aria-hidden="true" />
              <div><strong>{item.title}</strong><span>{item.message}</span></div>
            </li>
          ))}
        </ul>
      </section>

      <section className="mabox-runtime-status__section" aria-labelledby="runtime-modules-heading">
        <div className="mabox-runtime-status__section-heading">
          <div>
            <h3 id="runtime-modules-heading">当前运行模块</h3>
            <p>仅列出本次实际加载的功能；作用范围可帮助判断问题出现在哪一侧。</p>
          </div>
        </div>
        <div className="mabox-runtime-status__groups">
          {groupedModules.map(([category, modules]) => (
            <section key={category} className="mabox-runtime-status__group" aria-label={category}>
              <h4>{category}<span>{modules.length}</span></h4>
              <ul>
                {modules.map((module) => (
                  <li key={module.id}>
                    <div className="mabox-runtime-status__module-copy">
                      <strong>{module.label}</strong>
                    </div>
                    <div className="mabox-runtime-status__module-meta">
                      {module.always_loaded && (
                        <span className="mabox-runtime-status__badge mabox-runtime-status__badge--active">
                          无需开关
                        </span>
                      )}
                      <span>{scopeLabels[module.scope]}</span>
                      {module.tier !== "core" && (
                        <span className={`mabox-runtime-status__tier mabox-runtime-status__tier--${module.tier}`}>
                          {tierLabels[module.tier]}
                        </span>
                      )}
                      {module.view && module.target_id && (
                        <a
                          href={createAdminTargetUrl(window.location.href, module.view, module.target_id)}
                          aria-label={`前往${module.label}设置`}
                          onClick={(event) => {
                            if (!onNavigate) return;
                            event.preventDefault();
                            onNavigate(module.view, module.target_id);
                          }}
                        >
                          前往设置
                        </a>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            </section>
          ))}
        </div>
      </section>

      <section className="mabox-runtime-status__section" aria-labelledby="runtime-editor-heading">
        <div className="mabox-runtime-status__section-heading">
          <div>
            <h3 id="runtime-editor-heading">始终可用的编辑器工具</h3>
            <p>这些工具不受设置开关控制，在文章或页面编辑器的 Npcink Site Toolbox 分类中使用。</p>
          </div>
        </div>
        <ul className="mabox-runtime-status__tool-list">
          {data.editor_tools.map((tool) => (
            <li key={tool.id}>
              <div><strong>{tool.title}</strong><span>{tool.description}</span></div>
              <span>{tool.type === "pattern" ? "区块样板" : "动态区块"}</span>
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
};

export default RuntimeStatus;
