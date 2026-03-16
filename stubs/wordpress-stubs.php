<?php
/**
 * WordPress function stubs for SonarCloud PHP analysis.
 *
 * These declarations tell SonarCloud the correct signatures for WordPress
 * functions that its built-in PHP stubs declare with zero parameters.
 * This file is excluded from analysis via sonar.exclusions.
 *
 * @see sonar-project.properties sonar.php.stubs
 */

/**
 * @param string   $tag
 * @param callable $function_to_remove
 * @param int      $priority
 * @return bool
 */
function remove_filter( $tag, $function_to_remove, $priority = 10 ) {}

/**
 * @param string $object_subtype
 * @param string $meta_key
 * @param array  $args
 * @return bool
 */
function register_post_meta( $object_subtype, $meta_key, $args ) {}

/**
 * @param string       $handle
 * @param string       $src
 * @param string[]     $deps
 * @param string|false $ver
 * @param string       $media
 */
function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {}

/**
 * @param string       $handle
 * @param string       $src
 * @param string[]     $deps
 * @param string|false $ver
 * @param bool         $in_footer
 */
function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) {}

/**
 * @param string        $widget_id
 * @param string        $widget_name
 * @param callable      $callback
 * @param callable|null $control_callback
 * @param array|null    $callback_args
 */
function wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null ) {}

/**
 * @param string          $id
 * @param string          $title
 * @param callable        $callback
 * @param string|null     $screen
 * @param string          $context
 * @param string          $priority
 * @param array|null      $callback_args
 */
function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {}
