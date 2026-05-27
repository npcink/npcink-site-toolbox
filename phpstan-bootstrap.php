<?php
// PHPStan bootstrap: declare WordPress constants and stubs not covered by wordpress-stubs

defined('ABSPATH') || define('ABSPATH', dirname(__DIR__) . '/');

defined('DAY_IN_SECONDS') || define('DAY_IN_SECONDS', 86400);
defined('HOUR_IN_SECONDS') || define('HOUR_IN_SECONDS', 3600);
defined('MINUTE_IN_SECONDS') || define('MINUTE_IN_SECONDS', 60);
defined('YEAR_IN_SECONDS') || define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);

defined('ARRAY_A') || define('ARRAY_A', 'ARRAY_A');
defined('ARRAY_N') || define('ARRAY_N', 'ARRAY_N');

defined('COOKIEPATH') || define('COOKIEPATH', '/');
defined('COOKIE_DOMAIN') || define('COOKIE_DOMAIN', false);

if (!class_exists('MaBox_Public')) {
    class MaBox_Public
    {
        public function __construct(string $plugin_name, string $version)
        {
        }
    }
}
