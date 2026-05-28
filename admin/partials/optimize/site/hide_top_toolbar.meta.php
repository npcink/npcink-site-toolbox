<?php
defined('ABSPATH') || exit;

return array(
    'class'      => 'MaBox_Hide_Top_Toolbar',
    'file'       => 'optimize/site/hide_top_toolbar.php',
    'option_key' => 'optimize.site.hide_top_toolbar',
    'category'   => 'optimize',
    'scope'      => 'both',
    'risk_tags'  => array('推荐', '仅后台'),
    'label'      => '隐藏顶部工具条',
    'group'      => '站点',
    'feature_id' => 'optimize-site-hide_top_toolbar',
    'risk'       => array('level' => 'none'),
    'depends_on' => array(),
    'preset_tags' => array('pure', 'blog'),
);