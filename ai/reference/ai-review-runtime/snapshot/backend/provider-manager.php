<?php
/**
 * AI 审核 Provider 管理器
 *
 * 单例模式，负责：
 * 1. 加载并激活当前配置的 Provider
 * 2. 同一时间仅一个 Provider 激活
 * 3. 无 API 配置时自动降级到本地规则引擎
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Ai_Provider_Manager')) {
    class MaBox_Ai_Provider_Manager {

        private static $instance = null;
        private $active_provider = null;
        private $config = array();

        private function __construct() {}

        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function set_config($config) {
            $this->config = $config;
            $this->active_provider = null;
        }

        public function get_active_provider() {
            if ($this->active_provider !== null) {
                return $this->active_provider;
            }

            $provider_name = !empty($this->config['provider']) ? $this->config['provider'] : 'local';

            $provider = $this->create_provider($provider_name);

            if ($provider === null || !$provider->is_available($this->config)) {
                $provider = new MaBox_Ai_Provider_Local_Rules();
            }

            $this->active_provider = $provider;
            return $provider;
        }

        public function get_available_providers() {
            return array(
                'deepseek'  => 'DeepSeek',
                'aliyun'    => '阿里云内容安全',
                'custom'    => '自定义 API',
                'local'     => '本地规则引擎',
            );
        }

        private function create_provider($name) {
            switch ($name) {
                case 'deepseek':
                    require_once dirname(__FILE__) . '/provider/deepseek.php';
                    return new MaBox_Ai_Provider_DeepSeek();
                case 'aliyun':
                    require_once dirname(__FILE__) . '/provider/aliyun.php';
                    return new MaBox_Ai_Provider_Aliyun();
                case 'custom':
                    require_once dirname(__FILE__) . '/provider/custom_api.php';
                    return new MaBox_Ai_Provider_Custom_Api();
                case 'local':
                default:
                    require_once dirname(__FILE__) . '/provider/local_rules.php';
                    return new MaBox_Ai_Provider_Local_Rules();
            }
        }

        public function review($text) {
            $provider = $this->get_active_provider();
            return $provider->review($text, $this->config);
        }
    }
}
