<?php

/**
 * 效果：未登录模糊文章内图片
 * 来源：https://www.npc.ink/19791.html
 */
if (!class_exists('Npcink_Unlisted_Vague_Img')) {
    class Npcink_Unlisted_Vague_Img
    {
        /**
         * 执行代码
         */
        public static  function run()
        {
            add_action('wp_footer', array(__CLASS__, 'unlisted_vague_img'));
        }

        /**
         * 功能代码
         */
        public static function unlisted_vague_img()
        {
            //判断是否登录
            if (!is_user_logged_in()) {
                echo '<style>
                /*仅模糊文章内图片*/
                .entry-content img {
                -webkit-filter: blur(10px)!important;
                  -moz-filter: blur(10px)!important;
                  -ms-filter: blur(10px)!important;
                  filter: blur(6px)!important;}
                  .entry-content img:before{
                    content:"登录可见";
                  }
                  </style>';
            }
        }
    }
}
