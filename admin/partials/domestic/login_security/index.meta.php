<?php
defined('ABSPATH') || exit;

return array(
    'class'       => 'MaBox_Domestic_Login_Security',
    'file'        => 'domestic/login_security/index.php',
    'option_key'  => 'domestic.login_security.fail_limit_enabled',
    'category'    => 'domestic',
    'scope'       => 'both',
    'config_path' => 'domestic.login_security',
    'risk_tags'   => array('推荐', '安全'),
    'label'       => '登录安全',
    'group'       => '登录安全',
    'feature_id'  => 'domestic-login_security',
    'risk'        => array(
        'level'      => 'high',
        'title'      => '自定义登录地址',
        'warning'    => '修改登录地址后，原 wp-login.php 将被重定向，配置错误可能导致无法登录。',
        'suggestion' => '记住新的登录地址，避免锁定自己。',
        'noDismiss'  => true,
    ),
    'depends_on'  => array(),
    'preset_tags' => array('security'),
);