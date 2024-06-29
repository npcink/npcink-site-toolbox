<?php

/**
 * 效果：移除插件设置选项内容
 */
if (!class_exists('MaBox_Config_Remove_Config')) {
    class MaBox_Config_Remove_Config
    {
        //卸载插件时执行
        public static function run()
        {
            /**
             * 引入获取选项的方法
             */
            require plugin_dir_path(__FILE__) . '../../../class-magick-mixtrue-admin.php';

            $function =  MaBox_Admin::get_seting('function');
            $config = MaBox_Admin::get_config($function, 'config');
            $remove_config =  MaBox_Admin::get_config($config, 'remove_config');

            if ($remove_config === true) {
                $deleted = delete_option(MAGICK_MIXTURE_OPTION);

                if ($deleted) {
                    // 成功删除选项的逻辑
                    echo '选项 MAGICK_MIXTURE_OPTION 已成功删除。';
                } else {
                    // 未能删除选项的逻辑
                    echo '无法删除选项 MAGICK_MIXTURE_OPTION。';
                }
            }
        }
    }
}
