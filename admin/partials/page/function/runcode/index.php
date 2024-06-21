<?php

/**
 * 效果：页面中添加运行代码的短代码
 * 来源：https://www.bber.cn/174.html
 */

if (!class_exists('Npcink_Page_Runcode')) {
    class Npcink_Page_Runcode
    {

        private static $blocks = array();

        public static function run()
        {
            //底部添加前端资源
            add_action('wp_footer', array('Npcink_Page_Runcode', 'add_runcode'));

            add_filter('the_content', array(__CLASS__, 'part_one'), -500);
            add_filter('the_content', array(__CLASS__, 'part_two'), 500);
            add_action('after_wp_tiny_mce', array(__CLASS__, 'add_custom_button'));
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
        public static function part_one($content)
        {
            $str_pattern = "/(\<runcode(.*?)\>(.*?)\<\/runcode\>)/is";
            if (preg_match_all($str_pattern, $content, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $code = htmlspecialchars($matches[3][$i]);
                    $code = preg_replace("/(\s*?\r?\n\s*?)+/", "\n", $code);
                    $num = rand(1000, 9999);
                    $id = "runcode_$num";
                    $blockID = "<p>++RUNCODE_BLOCK_$num++";
                    $innertext = '
                    <div class="runcode-box">
                        <div class="runcode-box-header">
                            <input class="runcode2" type="button" value="运行代码" onclick="runCode(\'' . $id . '\')"/>
                           <!-- <input class="btn btn--secondary" style="margin-left: 30px;" type="button" value="全选代码" onclick="selectCode(\'' . $id . '\')"/>-->
                            <input class="btn btn--secondary" style="margin-left: 30px;" type="button" value="复制代码" onclick="copyCode(\'' . $id . '\')"/>
                        </div>
                        <textarea readonly id="' . $id . '" class="runcode" style="height: auto; min-height: 150px; max-height: 300px; overflow-y: auto;">' . $code . '</textarea>
                        </div>
                    ';
                    self::$blocks[$blockID] = $innertext;
                    $content = str_replace($matches[0][$i], $blockID, $content);
                }
            }
            return $content;
        }

        public static function part_two($content)
        {
            if (count(self::$blocks)) {
                $content = str_replace(array_keys(self::$blocks), array_values(self::$blocks), $content);
                self::$blocks = array();
            }
            return $content;
        }

        public static function add_custom_button($mce_settings)
        {
        ?>
            <script type="text/javascript">
                QTags.addButton('shipindai', '代码运行', '\n<runcode>\n', '\n</runcode>\n');

                function bolo_QTnextpage_arg1() {}
            </script>
<?php
        }
    }
}
