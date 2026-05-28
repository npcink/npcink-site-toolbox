<?php
defined('ABSPATH') || exit;

if (!class_exists('MaBox_Rest_Route_Registry')) {
    class MaBox_Rest_Route_Registry {

        private static $routes = array();

        private static $namespace = 'mabox/v1';

        public static function add($path, $args, $group = '') {
            self::$routes[] = array(
                'path'  => $path,
                'args'  => $args,
                'group' => $group,
            );
        }

        public static function register_all() {
            foreach (self::$routes as $route) {
                register_rest_route(self::$namespace, $route['path'], $route['args']);
            }
        }

        public static function get_registered() {
            return self::$routes;
        }

        public static function clear() {
            self::$routes = array();
        }

        public static function admin_permission() {
            return function () {
                return current_user_can('manage_options');
            };
        }

        public static function public_nonce_rate_limited($endpoint, $nonce_action, $limits = array()) {
            return MaBox_Rate_Limiter::permission_callback_with_nonce($endpoint, $nonce_action, $limits);
        }

        public static function get_route_count() {
            return count(self::$routes);
        }

        public static function get_routes_by_group($group) {
            $result = array();
            foreach (self::$routes as $route) {
                if ($route['group'] === $group) {
                    $result[] = $route;
                }
            }
            return $result;
        }

        public static function validate_all_have_permission() {
            $missing = array();
            foreach (self::$routes as $route) {
                $has_permission = false;

                if (is_array($route['args'])) {
                    if (isset($route['args']['permission_callback'])) {
                        $has_permission = true;
                    } elseif (isset($route['args'][0])) {
                        foreach ($route['args'] as $endpoint) {
                            if (is_array($endpoint) && isset($endpoint['permission_callback'])) {
                                $has_permission = true;
                                break;
                            }
                        }
                    }
                }

                if (!$has_permission) {
                    $missing[] = $route['path'];
                }
            }
            return $missing;
        }
    }
}