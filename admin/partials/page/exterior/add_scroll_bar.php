<?php

/**
 * 效果：美化滚动条
 * 来源：
 * TODO:兼容更多浏览器
 */

if (!class_exists('MaBox_Page_Add_Scroll_Bar')) {
    class MaBox_Page_Add_Scroll_Bar
    {
        public static $config; //配置
        public static function run($config)
        {
            self::$config = $config;
            add_action('wp_enqueue_scripts', array(__CLASS__, 'scrol'));
           
        }



        public static function scrol()
        {
            $default = '<style>/*—滚动条默认显示样式–*/  
            ::-webkit-scrollbar-thumb{   
                background-color:#292929;   
                height:50px;   
                outline-offset:-2px;   
                outline:2px solid #fff;   
                -webkit-border-radius:4px;   
                border: 2px solid #fff;   
            }   
              
            /*—鼠标点击滚动条显示样式–*/  
            ::-webkit-scrollbar-thumb:hover{   
                background-color:#292929;   
                height:50px;   
                -webkit-border-radius:4px;   
            }   
              
            /*—滚动条大小–*/  
            ::-webkit-scrollbar{   
                width:10px;   
                height:10px;   
            }   
              
            /*—滚动框背景样式–*/  
            ::-webkit-scrollbar-track-piece{   
                background-color:#fff;   
                -webkit-border-radius:0;   
            } </style>';

            //彩条
            $color = '<style>
            /*滚动条样式*/
            ::-webkit-scrollbar {/*滚动条整体样式*/
              width: 10px;     /*高宽分别对应横竖滚动条的尺寸*/
              height: 1px;
            }
            ::-webkit-scrollbar-thumb {/*滚动条里面小方块*/
              background-color: #12b7f5;
              background-image: -webkit-linear-gradient(45deg, rgba(255, 93, 143, 1) 25%, transparent 25%, transparent 50%, rgba(255, 93, 143, 1) 50%,
              rgba(255, 93, 143, 1) 75%, transparent 75%, transparent);
            }
            ::-webkit-scrollbar-track {/*滚动条里面轨道*/
                -webkit-box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
                background: #f6f6f6;
            }</style>';

            $scrol = self::$config;
            if ($scrol === "default") {
                echo $default;
            }
            if ($scrol === "color") {
                echo $color;
            }
        }
    }
}
