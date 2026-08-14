

var __ = window.wp && window.wp.i18n && window.wp.i18n.__
  ? window.wp.i18n.__
  : function (text) { return text; };

var countdownLabels = {
  ended: __("倒计时结束", "npcink-site-toolbox"),
  days: __("天", "npcink-site-toolbox"),
  hours: __("时", "npcink-site-toolbox"),
  minutes: __("分", "npcink-site-toolbox"),
  seconds: __("秒", "npcink-site-toolbox")
};

// 更新倒计时的函数
function updateCountdown() {
    var currentDate = new Date(); // 当前日期和时间

    // 计算剩余时间
    var remainingTime = targetDate - currentDate;

    // 如果目标日期已过，则显示倒计时结束
    if (remainingTime <= 0) {
      document.getElementById("countdown").textContent = countdownLabels.ended;
      return;
    }
  
    // 计算剩余的天、小时、分钟和秒
    var days = Math.floor(remainingTime / (1000 * 60 * 60 * 24));
    var hours = Math.floor((remainingTime / (1000 * 60 * 60)) % 24);
    var minutes = Math.floor((remainingTime / 1000 / 60) % 60);
    var seconds = Math.floor((remainingTime / 1000) % 60);
  
    // 格式化时间并显示在页面上
    var countdownString =
      ' <ul class="countdown-content"><li> <span class="digits days">' +
      days +
      '</span> <i>:</i> <span class="label">' + countdownLabels.days + '</span></li><li> <span class="digits hours">' +
      hours.toString().padStart(2, "0") +
      '</span> <i>:</i> <span class="label">' + countdownLabels.hours +
      '</span></li><li> <span class="digits minutes">' +
      minutes.toString().padStart(2, "0") +

      '</span> <i>:</i> <span class="label">' + countdownLabels.minutes + '</span> </li><li> <span class="digits seconds">' +
      seconds.toString().padStart(2, "0") +
      '</span> <span class="label">' + countdownLabels.seconds + '</span> </li></ul>';
    document.getElementById("countdown").innerHTML = countdownString;

    // 每秒钟更新一次倒计时
    setTimeout(updateCountdown, 1000);
  }

  // 页面加载完成后开始倒计时
  window.onload = function () {
    updateCountdown();
  };
