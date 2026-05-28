<?php
defined('ABSPATH') || exit;

return array(
    'class'       => 'MaBox_CDN_Replace',
    'file'        => 'optimize/site/cdn_replace.php',
    'option_key'  => 'optimize.site.cdn_replace',
    'category'    => 'optimize',
    'scope'       => 'frontend',
    'config_path' => 'optimize.site',
    'risk_tags'   => array('性能'),
    'label'       => '国内 CDN 替换',
    'group'       => '站点',
    'feature_id'  => 'optimize-site-cdn_replace',
    'risk'        => array('level' => 'low'),
    'depends_on'  => array(),
    'preset_tags' => array('performance'),
);