<?php
defined('ABSPATH') || exit;

return array(
    'class'      => 'MaBox_No_Escape',
    'file'       => 'optimize/site/no_escape.php',
    'option_key' => 'optimize.site.no_escape',
    'category'   => 'optimize',
    'scope'      => 'frontend',
    'risk_tags'  => array('推荐'),
    'label'      => '禁止 Title 转义',
    'group'      => '站点',
    'feature_id' => 'optimize-site-no_escape',
    'risk'       => array('level' => 'none'),
    'depends_on' => array(),
    'preset_tags' => array('pure', 'blog'),
);