<?php
defined('ABSPATH') || exit;

return array(
    'class'       => 'MaBox_ShortCode',
    'file'        => 'shortcode/index.php',
    'option_key'  => 'shortcode',
    'category'    => 'shortcode',
    'scope'       => 'both',
    'always_load' => true,
    'label'       => '短代码',
    'group'       => '板式',
    'feature_id'  => 'shortcode-main',
    'risk'        => array('level' => 'none'),
    'depends_on'  => array(),
    'preset_tags' => array(),
);