<?php

/**
 * 功能：短代码 挂件
 */
if (!class_exists('MaBox_ShortCode_Pendant')) {
    class MaBox_ShortCode_Pendant  extends MaBox_ShortCode
    {
        public static function runs($option)
        {
            //中国足迹地图
            $merc_map = MaBox_Admin::get_config($option, 'merc_map');
            if ($merc_map === true) {

                //地图数据
                $merc_location = MaBox_Admin::get_config($option, 'merc_location');
                require_once plugin_dir_path(__FILE__) . 'merc_map/index.php';
                MaBox_ShortCode_Merc_Map::run($merc_location);
                //下拉中添加短代码
                //这里需要进行转义，不然会丢失部分短代码内容
                self::$option_list .= '
                <option value="[mabox_cn_map]">足迹地图</option>
              ';
            }
        }
    } //end
}
