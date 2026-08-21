/**
 * 页面优化 - 评论
 */
import { useState, useContext, useEffect } from "react";
import { Form, InputNumber, Input, Radio, Switch } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageComment } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";

type FieldType = PageComment;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.page?.comment || defaultVarOption.page.comment;

  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  useEffect(() => {
    updateOption("page", "comment", formData);
  }, [formData]);

  return (
    <SettingsSection title={__("评论")}>
      <Form
        name="comment"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title={__("两次评论间隔时间")}
          description={__("避免短时间内重复灌水评论，对管理员无效")}
          featureId="page-comment-interval"
          enabled={formData.interval as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ interval: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/19960.html?mami", "_blank")}
        >
          <Form.Item<FieldType>
            label={__("时间间隔")}
            name="interval_time"
            extra={__("指定时间后才能再次评论")}
          >
            <InputNumber min={0} addonAfter={__("秒")} />
          </Form.Item>
        </ModuleRow>
        <ModuleRow
          title={__("限制评论字数")}
          description={__("指定最小和最大评论字数")}
          featureId="page-comment-words_number"
          enabled={formData.words_number as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ words_number: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/17995.html?mami", "_blank")}
        >
          <Form.Item<FieldType> label={__("最小字数")} name="words_number_min">
            <InputNumber min={0} addonAfter={__("字")} />
          </Form.Item>
          <Form.Item<FieldType> label={__("最大字数")} name="words_number_max">
            <InputNumber min={0} addonAfter={__("字")} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("禁止纯英文评论")}
          featureId="page-comment-english"
          enabled={formData.english as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ english: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/18129.html?mami", "_blank")}
        />

        <ModuleRow
          title={__("单篇文章仅限评论一次")}
          description={__("管理员不受此影响")}
          featureId="page-comment-only"
          enabled={formData.only as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ only: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title={__("敏感词过滤")}
          description={__("评论提交时检测敏感词，替换或拦截")}
          featureId="page-comment-sensitive_words"
          enabled={formData.sensitive_words as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ sensitive_words: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item<FieldType>
            label={__("敏感词列表")}
            name="sensitive_words_list"
            extra={__("每行一个敏感词")}
          >
            <Input.TextArea rows={6} placeholder={__("敏感词1\n敏感词2")} />
          </Form.Item>
          <Form.Item<FieldType>
            label={__("处理方式")}
            name="sensitive_words_action"
          >
            <Radio.Group>
              <Radio value="replace">{__("替换为 ***")}</Radio>
              <Radio value="block">{__("拦截并阻止提交")}</Radio>
            </Radio.Group>
          </Form.Item>
          <Form.Item<FieldType>
            label={__("替换字符")}
            name="sensitive_words_replace_char"
            extra={__("选择替换方式时生效")}
          >
            <Input style={{ width: "30%" }} placeholder="***" />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("用户评论 REST 接口")}
          description={__("允许外部客户端通过 WordPress 应用程序密码发布、修改和删除当前用户自己的评论")}
          featureId="page-comment-self_service_enabled"
          enabled={formData.self_service_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange(
              { self_service_enabled: checked } as Partial<FieldType>,
              formData
            );
          }}
          onDetails={() =>
            window.open(
              window.npcinkSiteToolboxData?.commentRestHelpUrl ||
                "admin.php?page=npcink-site-toolbox-comment-rest-help",
              "_blank",
              "noopener,noreferrer"
            )
          }
        >
          <Form.Item<FieldType>
            label={__("显示“我的评论”后台页面")}
            name="self_service_admin_page_enabled"
            valuePropName="checked"
            extra={__("作为浏览器内的兜底操作界面；关闭后 REST 接口仍可使用")}
          >
            <Switch disabled={!formData.self_service_enabled} />
          </Form.Item>
        </ModuleRow>
      </Form>
    </SettingsSection>
  );
};

export default App;
