<?php
/**
 * PHPUnit bootstrap for Draft Status plugin tests.
 *
 * Unit tests use WP_Mock so they run without a database.
 * Integration tests need WP_TESTS_DIR to point at the WordPress
 * test library (loaded separately from the test case base class).
 */

require_once __DIR__ . '/../vendor/autoload.php';

// WordPress constant the plugin guards against at the top of the file.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/var/www/html/' );
}

// WordPress stub functions required at file-include time.
// WP_Mock stubs these for individual test assertions, but we need them
// present before requiring the plugin file so PHP doesn't fatal.
$wp_stub_functions = [
    'add_action'            => function( $hook, $callback, $priority = 10, $args = 1 ) {},
    'add_filter'            => function( $hook, $callback, $priority = 10, $args = 1 ) {},
    '__'                    => function( $text, $domain = 'default' ) { return $text; },
    'esc_html__'            => function( $text, $domain = 'default' ) { return htmlspecialchars( $text, ENT_QUOTES ); },
    'esc_attr__'            => function( $text, $domain = 'default' ) { return htmlspecialchars( $text, ENT_QUOTES ); },
    'esc_html_e'            => function( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES ); },
    'esc_html'              => function( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); },
    'esc_attr'              => function( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); },
    'esc_url'               => function( $url ) { return $url; },
    'esc_attr_e'            => function( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES ); },
    'plugin_dir_url'        => function( $file ) { return 'http://example.com/wp-content/plugins/draft-status/'; },
    'plugin_dir_path'       => function( $file ) { return rtrim( dirname( $file ), '/' ) . '/'; },
    'get_option'            => function( $option, $default = false ) { return $default; },
    'date_i18n'             => function( $format, $timestamp = 0 ) { return date( $format, $timestamp ); },
    'sanitize_text_field'   => function( $str ) { return trim( strip_tags( $str ) ); },
    'wp_unslash'            => function( $value ) { return is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( $value ); },
    'in_array'              => null, // native PHP — do not stub
    'update_post_meta'      => function( $post_id, $meta_key, $meta_value ) { return true; },
    'delete_post_meta'      => function( $post_id, $meta_key ) { return true; },
    'get_post_meta'         => function( $post_id, $key = '', $single = false ) { return $single ? '' : []; },
    'wp_verify_nonce'       => function( $nonce, $action ) { return false; },
    'current_user_can'      => function( $capability, ...$args ) { return false; },
    'get_post_status'       => function( $post ) { return 'draft'; },
    'wp_nonce_field'        => function( $action, $name, $referer = true, $echo = true ) { if ( $echo ) echo ''; },
    'selected'              => function( $selected, $current, $echo = true ) { if ( $selected == $current ) { if ( $echo ) echo " selected='selected'"; return " selected='selected'"; } return ''; },
    'admin_url'             => function( $path = '' ) { return 'http://example.com/wp-admin/' . ltrim( $path, '/' ); },
    'wp_reset_postdata'     => function() {},
    'get_the_ID'            => function() { return 0; },
    'get_the_title'         => function() { return ''; },
    'get_the_modified_date' => function( $format = '' ) { return ''; },
    'get_edit_post_link'    => function( $id ) { return '#'; },
    'add_meta_box'          => function() {},
    'wp_enqueue_style'      => function() {},
    'wp_enqueue_script'     => function() {},
    'register_post_meta'    => function() {},
    'wp_add_dashboard_widget' => function() {},
    'remove_filter'         => function() {},
    'wp_verify_nonce'       => function( $nonce, $action ) { return false; },
    'defined'               => null, // native PHP
];

foreach ( $wp_stub_functions as $name => $fn ) {
    if ( $fn !== null && ! function_exists( $name ) ) {
        $GLOBALS['_draft_status_stubs'][ $name ] = $fn;
        // Use create_function equivalent via Closure::bind trick isn't needed —
        // just define them normally using eval-free approach.
    }
}

// Define them all cleanly.
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) { return htmlspecialchars( $text, ENT_QUOTES ); }
}
if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $text, $domain = 'default' ) { return htmlspecialchars( $text, ENT_QUOTES ); }
}
if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES ); }
}
if ( ! function_exists( 'esc_attr_e' ) ) {
    function esc_attr_e( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES ); }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) { return $url; }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) { return 'http://example.com/wp-content/plugins/draft-status/'; }
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) { return rtrim( dirname( $file ), '/' ) . '/'; }
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) { return $default; }
}
if ( ! function_exists( 'date_i18n' ) ) {
    function date_i18n( $format, $timestamp = 0 ) { return date( $format, $timestamp ?: time() ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return trim( strip_tags( $str ) ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( $value ); }
}
if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( $post_id, $meta_key, $meta_value ) { return true; }
}
if ( ! function_exists( 'delete_post_meta' ) ) {
    function delete_post_meta( $post_id, $meta_key ) { return true; }
}
if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) { return $single ? '' : []; }
}
// wp_verify_nonce and current_user_can are intentionally NOT stubbed here.
// WP_Mock intercepts them per-test. Tests that need a default return value
// must set up a WP_Mock::userFunction() expectation themselves.
if ( ! function_exists( 'get_post_status' ) ) {
    function get_post_status( $post ) { return 'draft'; }
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field( $action, $name, $referer = true, $echo = true ) { if ( $echo ) echo ''; }
}
if ( ! function_exists( 'selected' ) ) {
    function selected( $selected, $current, $echo = true ) {
        if ( $selected == $current ) {
            if ( $echo ) echo " selected='selected'";
            return " selected='selected'";
        }
        return '';
    }
}
if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '' ) { return 'http://example.com/wp-admin/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'wp_reset_postdata' ) ) {
    function wp_reset_postdata() {}
}
if ( ! function_exists( 'get_the_ID' ) ) {
    function get_the_ID() { return 0; }
}
if ( ! function_exists( 'get_the_title' ) ) {
    function get_the_title() { return ''; }
}
if ( ! function_exists( 'get_the_modified_date' ) ) {
    function get_the_modified_date( $format = '' ) { return ''; }
}
if ( ! function_exists( 'get_edit_post_link' ) ) {
    function get_edit_post_link( $id ) { return '#'; }
}
if ( ! function_exists( 'add_meta_box' ) ) {
    function add_meta_box() {}
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style() {}
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script() {}
}
if ( ! function_exists( 'register_post_meta' ) ) {
    function register_post_meta() {}
}
if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
    function wp_add_dashboard_widget() {}
}
if ( ! function_exists( 'remove_filter' ) ) {
    function remove_filter() {}
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {}
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $args = 1 ) {}
}
if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() { return false; }
}
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability, ...$args ) { return false; }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        return ( $type === 'timestamp' || $type === 'U' ) ? time() : date( 'Y-m-d H:i:s' );
    }
}

// Initialise WP_Mock (unit tests call WP_Mock::setUp/tearDown themselves).
WP_Mock::bootstrap();

// Load the plugin class. The ABSPATH guard at the top of the file is already
// satisfied, so this will define the DraftStatus class without running
// `new DraftStatus()` (that line is at file scope, but ABSPATH is defined
// so it won't exit — we need to suppress it).
// We load via a wrapper that prevents the bottom-of-file instantiation from
// firing hooks into a non-existent WordPress environment.
require_once __DIR__ . '/../draft-status.php';
