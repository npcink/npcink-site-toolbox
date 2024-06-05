const sectionElement = document.querySelector(".site-sharing-container");

const toggleButton = document.getElementById("toggleButton");

let isToggled = false;

const activeType = () => {
  if (isToggled) {
    sectionElement.classList.remove("opened");
  } else {
    sectionElement.classList.add("opened");
  }
  isToggled = !isToggled;
  console.log("当前状态");
  console.log(isToggled);
};

//封装
function clickoutSide(nameClass, callback) {
  // 全局注册点击事件
  document.onclick = function (e) {
    // 若点击元素为目标元素或目标元素的子元素，则返回
    const targetElement = e.target.closest("." + nameClass);
    if (
      (targetElement && targetElement.classList.contains(nameClass)) ||
      e.target.className === "btn"
    ) {
      return;
    }
    // 否则执行回调函数
    callback();
  };
}

//点击空白处关闭弹窗
clickoutSide("site-sharing-content", function () {
  if (isToggled) {
    activeType();
  }
});

//复制链接
const copyLink = () => {
  const url = window.location.href; /* 获取完整URL */

  // 创建一个临时的textarea元素
  const tempTextArea = document.createElement("textarea");
  tempTextArea.value = url;

  // 将textarea元素添加到页面中
  document.body.appendChild(tempTextArea);

  // 选择textarea中的文本并执行复制命令
  tempTextArea.select();
  document.execCommand("copy");

  // 移除临时的textarea元素
  document.body.removeChild(tempTextArea);

  //关闭弹窗
  activeType();
};
