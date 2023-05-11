//3小时算一次
function record_image_view(image_id) {
  console.log("我执行了");
  console.log(image_id);
  //发出统计
  const tj = () => {
    jQuery.ajax({
      type: "POST",
      url: "/wp-admin/admin-ajax.php",
      data: {
        action: "record_image_view",
        image_id: image_id,
      },
      success: function (response) {
        alert(response);
      },
    });
  };

  let lastClickTime = parseInt(localStorage.getItem("lastClickTime")) || "0";
  //拿到当前时间
  const now = Date.now();
  if (now - lastClickTime < 3 * 60 * 60 * 1000) {
    // 如果与上次点击时间相差不到3小时，则不算一次有效点击
    console.log("等等吧");
    return;
  }

  // 更新lastClickTime并写入localStorage
  localStorage.setItem("lastClickTime", now);
  //发出统计请求
  tj();
}

//重复统计
function record_image_views(data) {
  console.log("1");
  //开始统计
  const tj = (data) => {
    jQuery.ajax({
      type: "POST",
      //url: "/wp-admin/admin-ajax.php",
      //下面的数据来自加载main.js时传递的变量
      url: ajax_object.ajaxurl,
      data: {
        action: "record_image_view",
        //需要发送的数据，在此处转换为 JSON 字符串
        data: JSON.stringify(data),
      },
      success: function (response) {
        console.log(response);
        console.log("数据保存成功！");
      },
      error: function (xhr, status, error) {
        console.error(xhr, status, error);
        console.log("数据保存失败！");
      },
    });
  };
  tj(data);
}

jQuery(document).ready(function ($) {
  //图片懒加载
  var images = $("img[data-src]");
  $(window).on("load scroll resize", function () {
    images.each(function () {
      if ($(this).offset().top <= $(window).scrollTop() + $(window).height()) {
        var dataSrc = $(this).attr("data-src");
        $(this).attr("src", dataSrc).removeAttr("data-src");
      }
    });
  });
});
