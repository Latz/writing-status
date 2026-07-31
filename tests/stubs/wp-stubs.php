<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress function and class stub definitions for unit tests.
 *
 * Every function here is a sensible no-op default. Brain Monkey patches over
 * any of them per-test when you call Functions\expect() or Functions\when().
 *
 * wp_verify_nonce(), current_user_can(), is_admin(), and get_post_status()
 * are intentionally NOT stubbed here — Brain Monkey intercepts them
 * per-test. Tests that need a default return value must set up a
 * Functions\when()/expect() expectation. (A real, defined function here
 * caused Functions\when() overrides to leak across tests in *other* test
 * files too, not just the same file — Brain Monkey patches already-defined
 * functions less reliably than undefined ones.)
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES); }
}
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES); }
}
if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') { echo htmlspecialchars($text, ENT_QUOTES); }
}
if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default') { echo htmlspecialchars($text, ENT_QUOTES); }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES); }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES); }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/writing-status/'; }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return rtrim(dirname($file), '/') . '/'; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        if ($option === 'date_format') {
            return 'F j, Y';
        }
        return $default;
    }
}
if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp = 0) { return date($format, $timestamp ?: time()); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return is_array($value) ? array_map('stripslashes', $value) : stripslashes($value); }
}
if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) { return true; }
}
if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key) { return true; }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) { return $single ? '' : []; }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name, $referer = true, $echo = true) { if ($echo) { echo ''; } }
}
if (!function_exists('selected')) {
    function selected($selected, $current, $echo = true) {
        if ($selected == $current) {
            if ($echo) { echo " selected='selected'"; }
            return " selected='selected'";
        }
        return '';
    }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') { return 'http://example.com/wp-admin/' . ltrim($path, '/'); }
}
if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {}
}
if (!function_exists('get_the_ID')) {
    function get_the_ID() { return 0; }
}
if (!function_exists('get_the_title')) {
    function get_the_title() { return ''; }
}
if (!function_exists('get_the_modified_date')) {
    function get_the_modified_date($format = '') { return ''; }
}
if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($id) { return '#'; }
}
if (!function_exists('add_meta_box')) {
    function add_meta_box() {}
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() {}
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() {}
}
if (!function_exists('register_post_meta')) {
    function register_post_meta() {}
}
if (!function_exists('wp_add_dashboard_widget')) {
    function wp_add_dashboard_widget() {}
}
if (!function_exists('remove_filter')) {
    function remove_filter() {}
}
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {}
}
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {}
}
if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return ($type === 'timestamp' || $type === 'U') ? time() : date('Y-m-d H:i:s');
    }
}
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}

// Minimal WP_Query stub for unit tests.
if (!class_exists('WP_Query')) {
    class WP_Query {
        public array  $query_vars = [];
        public int    $found_posts = 0;
        private array $_posts = [];
        private int   $_index  = 0;
        private bool  $_is_main = true;

        public function __construct(array $args = []) {
            $this->query_vars = $args;
        }

        public function is_main_query(): bool { return $this->_is_main; }
        public function set_main(bool $v): void { $this->_is_main = $v; }

        public function get(string $key, $default = '') {
            return $this->query_vars[$key] ?? $default;
        }
        public function set(string $key, $value): void {
            $this->query_vars[$key] = $value;
        }
        public function have_posts(): bool {
            return $this->_index < count($this->_posts);
        }
        public function the_post(): void { $this->_index++; }
        public function set_posts(array $posts): void {
            $this->_posts      = $posts;
            $this->found_posts = count($posts);
            $this->_index      = 0;
        }
    }
}
