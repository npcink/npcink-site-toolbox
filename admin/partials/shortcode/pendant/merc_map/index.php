<?php

/**
 * 功能：足迹地图
 * 来源：https://github.com/HelloWuJiaYi/jVectorMap-Footprint
 */
if (!class_exists('MaBox_ShortCode_Merc_Map')) {
    class MaBox_ShortCode_Merc_Map
    {
        public static $location;
        public static function run($merc_location)
        {
            self::$location = $merc_location;

            $default_value = self::$location;

            printf('<script>console.log(%s)</script>', json_encode($default_value));
            //添加短代码
            add_shortcode('mabox_cn_map', array(__CLASS__, 'mabox_cn_map_shortcode'));

            //判断当前页面是否有 mabox_cn_map 短代码，如果有则加载 加载前端资源
            add_action('wp', array(__CLASS__, 'check_for_mabox_cn_map_shortcode'));
        }


        public static function check_for_mabox_cn_map_shortcode()
        {
            global $post;

            // 如果不是单篇文章页面或页面内容中不包含 mabox_cn_map 短代码，则不加载资源
            if (!is_singular() || !has_shortcode($post->post_content, 'mabox_cn_map')) {
                return;
            }

            add_action('wp_footer', array(__CLASS__, 'add_map_node'));
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_js'));
        }

        //短代码内容
        public static function mabox_cn_map_shortcode($atts, $content = null)
        {
            $html = '
              <!--background-color: 地图背景颜色-->
              
            <div id="map" style="background-color:#f4f4f4;height:550px"></div>
';
            return $html;
        }
        public static function add_map_node()
        {
?>

            <script>
                jQuery(document).ready(function($) {

                    $('#map').vectorMap({

                        // 此处更改地图
                        map: 'cn_merc_en', // 中国地图
                        //map: 'us_aea',     // 美国地图
                        //map: 'world_mill', // 世界地图


                        backgroundColor: 'transparent',
                        zoomMin: 0.9, // 鼠标缩放时的最小比例
                        zoomMax: 5, // 鼠标缩放时的最大比例
                        focusOn: {
                            x: 0.55,
                            y: 2,
                            scale: 0.9
                        },
                        regionStyle: {
                            initial: {
                                fill: '#e5e5e5', // 地图颜色
                                "fill-opacity": 1, // 省份（州）是否隐藏，鼠标滑动时显示; 1：显示，2：隐藏。
                                stroke: 'none',
                                "stroke-width": 0,
                                "stroke-opacity": 1
                            },
                            hover: {
                                fill: '#ccc', // 鼠标滑动至某省份的高亮颜色。
                                "fill-opacity": 0.8
                            },
                            selected: {
                                fill: 'yellow'
                            },
                            selectedHover: {}
                        },
                        markerStyle: {
                            initial: {
                                fill: '#fd8888', // 足迹位置的填充颜色
                                stroke: '#fff' // 足迹位置的描边颜色
                            },
                            hover: {
                                fill: '#fd2020', // 鼠标滑动至足迹位置后的填充颜色
                                stroke: '#fff', // 鼠标滑动至足迹位置后的描边颜色
                                "fill-opacity": 0.8
                            },
                        },
                        markers: <?php echo json_encode(self::$location); ?>,
                    });
                });
            </script>
<?php
        }

        //加载JS
        public static function load_js()
        {
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备css
            $build_css =  plugin_dir_url(__DIR__) . 'merc_map/jquery-jvectormap-1.2.2.css';
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_public_merc_map_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
            //准备js 
            $build_js =  plugin_dir_url(__DIR__) . 'merc_map/jquery-jvectormap-1.2.2.min.js';
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_jvectormap_js',
                $build_js,
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                false
            );
            //准备js 
            $merc_js =  plugin_dir_url(__DIR__) . 'merc_map/jquery-jvectormap-cn-merc-en.js';
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_cn-merc_js',
                $merc_js,
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }
    }
}
