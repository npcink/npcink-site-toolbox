const copyButton = document.getElementById("copyButton");
const siteContent = document.getElementById("site").textContent;

copyButton.addEventListener("click", () => {
  // 创建临时textarea元素
  const textarea = document.createElement("textarea");
  textarea.value = siteContent;
  document.body.appendChild(textarea);

  // 选中内容并复制到剪贴板
  textarea.select();
  document.execCommand("copy");

  // 清理临时元素
  document.body.removeChild(textarea);

  // 提示复制成功（可选）
  alert("网址已复制！");
});
