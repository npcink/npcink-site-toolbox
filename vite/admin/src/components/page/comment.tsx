/**
 * 页面优化 - 评论
 */
import { useState, useContext, useEffect } from "react";
import { Form, InputNumber, Input, Radio } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageComment } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

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
    <SettingsSection title="评论">
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
          title="两次评论间隔时间"
          description="避免短时间内重复灌水评论，对管理员无效"
          featureId="page-comment-interval"
          enabled={formData.interval as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ interval: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/19960.html?mami", "_blank")}
        >
          <Form.Item<FieldType>
            label="时间间隔"
            name="interval_time"
            extra={"指定时间后才能再次评论"}
          >
            <InputNumber min={0} addonAfter="秒" />
          </Form.Item>
        </ModuleRow>
        <ModuleRow
          title="限制评论字数"
          description="指定最小和最大评论字数"
          featureId="page-comment-words_number"
          enabled={formData.words_number as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ words_number: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/17995.html?mami", "_blank")}
        >
          <Form.Item<FieldType> label="最小字数" name="words_number_min">
            <InputNumber min={0} addonAfter="字" />
          </Form.Item>
          <Form.Item<FieldType> label="最大字数" name="words_number_max">
            <InputNumber min={0} addonAfter="字" />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title="禁止纯英文评论"
          featureId="page-comment-english"
          enabled={formData.english as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ english: checked } as Partial<FieldType>, formData);
          }}
          onDetails={() => window.open("https://www.npc.ink/18129.html?mami", "_blank")}
        />

        <ModuleRow
          title="单篇文章仅限评论一次"
          description="管理员不受此影响"
          featureId="page-comment-only"
          enabled={formData.only as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ only: checked } as Partial<FieldType>, formData);
          }}
        />
        <ModuleRow
          title="敏感词过滤"
          description="评论提交时检测敏感词，替换或拦截"
          featureId="page-comment-sensitive_words"
          enabled={formData.sensitive_words as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ sensitive_words: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item<FieldType>
            label="敏感词列表"
            name="sensitive_words_list"
            extra={"每行一个敏感词"}
          >
            <Input.TextArea rows={6} placeholder={"敏感词1&#10;敏感词2"} />
          </Form.Item>
          <Form.Item<FieldType>
            label="处理方式"
            name="sensitive_words_action"
          >
            <Radio.Group>
              <Radio value="replace">替换为 ***</Radio>
              <Radio value="block">拦截并阻止提交</Radio>
            </Radio.Group>
          </Form.Item>
          <Form.Item<FieldType>
            label="替换字符"
            name="sensitive_words_replace_char"
            extra={"选择替换方式时生效"}
          >
            <Input style={{ width: "30%" }} placeholder="***" />
          </Form.Item>
        </ModuleRow>
      </Form>
    </SettingsSection>
  );
};

export default App;
