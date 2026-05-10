<?php

/**
 * 效果：页面中添加运行代码的短代码
 * 来源：https://www.bber.cn/174.html
 * TODO:代码前后有换行符
 */
if (!class_exists('MaBox_Page_Runcode')) {
    class MaBox_Page_Runcode
    {
        public static function run()
        {
            add_shortcode('runcode', array(__CLASS__, 'shortcode_handler'));

            //判断当前页面是否有 mabox_cn_map 短代码，如果有则加载 加载前端资源
            add_action('wp', array(__CLASS__, 'runcode_shortcode'));
        }

        public static function runcode_shortcode()
        {
            global $post;

            // 如果不是单篇文章页面或页面内容中不包含 runcode 短代码，则不加载资源
            if (!is_singular() || !has_shortcode($post->post_content, 'runcode')) {
                return;
            }

            //底部添加前端资源
            add_action('wp_footer', array(__CLASS__, 'add_runcode'));
        }

        public static function shortcode_handler($atts, $content = null)
        {
            $code = htmlspecialchars($content);
            $code = preg_replace("/(\s*?\r?\n\s*?)+/", "\n", $code);
            $num = rand(1000, 9999);
            $id = "runcode_$num";
            $output = '
                <div class="runcode-box">
                    <div class="runcode-box-header">
                        <input class="runcode2" type="button" value="运行代码" onclick="runCode(\'' . $id . '\')"/>
                        <!-- <input class="btn btn--secondary" style="margin-left: 30px;" type="button" value="全选代码" onclick="selectCode(\'' . $id . '\')"/>-->
                        <input class="btn btn--secondary" style="margin-left: 30px;" type="button" value="复制代码" onclick="copyCode(\'' . $id . '\')"/>
                    </div>
                    <textarea readonly id="' . $id . '" class="runcode" style="height: auto; min-height: 150px; max-height: 300px; overflow-y: auto;">' . $code . '</textarea>
                </div>
            ';
            return $output;
        }

        public static function add_runcode()
        {
            echo '<style>.runcode{width:100%;margin-top:.8em;border-radius:8px;border:1px solid;padding:.6em;font-size:12px}</style>' . "\n";
            echo '<script>function runCode(o){var w=window.open("","_blank",""),d=document.getElementById(o);w.document.open("text/HTML","replace"),w.opener=null,w.document.write(d.value),w.document.close()}function copyCode(o){var t=document.getElementById(o);t.select(),t.setSelectionRange(0,99999),document.execCommand("copy"),window.getSelection().removeAllRanges(),alert("代码已复制到剪贴板！")}</script>' . "\n";
        }
    }
}
