import type { Components } from "react-markdown";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";

interface SafeMarkdownProps {
  markdown: string;
  className?: string;
}

const allowedElements = [
  "a",
  "blockquote",
  "br",
  "code",
  "del",
  "em",
  "h1",
  "h2",
  "h3",
  "h4",
  "h5",
  "h6",
  "hr",
  "input",
  "li",
  "ol",
  "p",
  "pre",
  "strong",
  "table",
  "tbody",
  "td",
  "th",
  "thead",
  "tr",
  "ul",
];

const components: Components = {
  // Diagnostic output is untrusted: keep link text without creating navigation.
  a: ({ children }) => <span>{children}</span>,
  h1: ({ children }) => <h5 data-markdown-level={1}>{children}</h5>,
  h2: ({ children }) => <h5 data-markdown-level={2}>{children}</h5>,
  h3: ({ children }) => <h5 data-markdown-level={3}>{children}</h5>,
  h4: ({ children }) => <h5 data-markdown-level={4}>{children}</h5>,
  h5: ({ children }) => <h5 data-markdown-level={5}>{children}</h5>,
  h6: ({ children }) => <h5 data-markdown-level={6}>{children}</h5>,
};

const SafeMarkdown = ({ markdown, className }: SafeMarkdownProps) => (
  <div className={className} aria-label="DeepSeek 分析结果内容">
    <ReactMarkdown
      allowedElements={allowedElements}
      components={components}
      remarkPlugins={[remarkGfm]}
      skipHtml
    >
      {markdown}
    </ReactMarkdown>
  </div>
);

export default SafeMarkdown;
