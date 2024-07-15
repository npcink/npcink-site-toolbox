<?php

/**
 * 效果：页面中添加运行代码的短代码
 * 来源：https://www.bber.cn/174.html
 * TODO:代码前后有换行符
 */
if (!class_exists('Npcink_Page_Runcode')) {
    class Npcink_Page_Runcode
    {
        public static function run()
        {
            add_shortcode('runcode', array('Npcink_Page_Runcode', 'shortcode_handler'));

            //底部添加前端资源
            add_action('wp_footer', array('Npcink_Page_Runcode', 'add_runcode'));
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
?>
            <style>
                /*文章内代码运行功能*/
                .runcode {
                    width: 100%;
                    margin-top: .8em;
                    border-radius: 8px;
                    border: 1px solid;
                    padding: .6em;
                    font-size: 12px;
                }
            </style>
            <!--代码运行模块-->
            <script type="text/javascript">
                function runCode(objid) {
                    var winname = window.open('', "_blank", '');
                    var obj = document.getElementById(objid);
                    winname.document.open('text/HTML', 'replace');
                    winname.opener = null;
                    winname.document.write(obj.value);
                    winname.document.close();
                }

                function selectCode(objid) {
                    var obj = document.getElementById(objid);
                    obj.select();
                }
            </script>

            <!--复制按钮功能-->
            <script>
                function copyCode(elementId) {
                    /* 获取文本区域的内容 */
                    var textarea = document.getElementById(elementId);
                    textarea.select();
                    textarea.setSelectionRange(0, 99999); /* 兼容性处理，确保能选中文本 */

                    /* 复制选中的内容到剪贴板 */
                    document.execCommand("copy");

                    /* 取消选中状态 */
                    window.getSelection().removeAllRanges();

                    /* 弹出提示信息 */
                    alert("代码已复制到剪贴板！");
                }
            </script>


            <!--代码运行模块-->
<?php
        }
    }
}
