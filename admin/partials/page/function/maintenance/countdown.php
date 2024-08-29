            <!--载入倒计时-->
            <link href="<?php echo $file_url . "css/Countdown.css" ?>" rel="stylesheet" type="text/css" />
            <script type="text/javascript" src="<?php echo $file_url . "js/Countdown.js" ?>"></script>
            <script>
                // 目标日期和时间
                var targetDate = new Date("<?php echo $countdown ?>"); //规定以T分隔日期和时间
            </script>
            <section class="countdown-container">
                <h3 class="countdown-desc">倒计时结束后即可正常访问</h3>
                <div id="countdown"></div>
            </section>