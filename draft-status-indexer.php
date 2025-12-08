<?php
/**
 * Plugin Name: Draft Status Indexer
 * Plugin URI: https://github.com/yourusername/draft-status-indexer
 * Description: Mark draft posts by completion status (complete/incomplete)
 * Version: 1.0.0
 * Author: Latz
 * Author URI: https://elektroelch.de
 * * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: draft-status-indexer
 * Domain Path: /languages
 */

// Prevent direct access to this file for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 *
 * Manages draft completion status for WordPress posts.
 * Adds a custom column to the posts list and a meta box to the post editor
 * allowing users to mark drafts as complete or incomplete for better workflow management.
 *
 * @since 1.0.0
 */
class DraftStatusIndexer {

    /**
     * Constructor - Initialize the plugin
     *
     * Hooks into WordPress to add columns, meta boxes, and handle data saving.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Enqueue admin styles - loads CSS only on relevant admin pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        // Add custom column to posts list - adds "Writing Status" column header
        add_filter('manage_posts_columns', array($this, 'add_completion_column'));

        // Display column content - shows status indicators in the column
        add_action('manage_posts_custom_column', array($this, 'display_completion_column'), 10, 2);

        // Make column sortable - allows clicking column header to sort
        add_filter('manage_edit-post_sortable_columns', array($this, 'make_completion_sortable'));

        // Handle sorting logic - processes the sort request
        add_action('pre_get_posts', array($this, 'sort_by_completion'));

        // Add meta box to edit screen - adds completion checkbox to post editor
        add_action('add_meta_boxes', array($this, 'add_completion_meta_box'));

        // Save completion status - saves checkbox value when post is saved
        add_action('save_post', array($this, 'save_completion_status'));

        // Add filter dropdown to posts list
        add_action('restrict_manage_posts', array($this, 'add_completion_filter_dropdown'));

        // Handle filter query
        add_filter('parse_query', array($this, 'filter_posts_by_completion'));

        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Enqueue admin stylesheets
     *
     * Loads the plugin's CSS file only on relevant admin pages to improve performance.
     * The CSS is only loaded on posts list and post editor pages.
     *
     * @since 1.0.0
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_styles($hook) {
        // Only load on edit.php (posts list), post.php (edit post), and post-new.php (new post) pages
        if ($hook !== 'edit.php' && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        // Enqueue the plugin stylesheet with versioning for cache busting
        wp_enqueue_style(
            'draft-status-indexer',                      // Handle
            plugin_dir_url(__FILE__) . 'DraftStatusIndexer.css', // Source
            array(),                                      // Dependencies
            '1.0.0'                                      // Version
        );
    }

    /**
     * Add custom column to posts list
     *
     * Adds a "Writing Status" column to the posts list table in the admin area.
     *
     * @since 1.0.0
     * @param array $columns Existing columns in the posts list table.
     * @return array Modified columns array with the new column added.
     */
    public function add_completion_column($columns) {
        // Add the "Writing Status" column to the posts list
        $columns['draft_completion'] = __('Writing Status', 'draft-status-indexer');
        return $columns;
    }

    /**
     * Display column content
     *
     * Outputs the status indicator (Published/Complete/Incomplete) for each post row.
     * Published posts show a blue indicator, complete drafts show green, incomplete show red.
     *
     * @since 1.0.0
     * @param string $column The column identifier.
     * @param int    $post_id The post ID for the current row.
     */
    public function display_completion_column($column, $post_id) {
        // Only process our custom column
        if ($column === 'draft_completion') {
            $post_status = get_post_status($post_id);

            // Published posts - show blue "Published" indicator
            if ($post_status === 'publish') {
                printf(
                    '<span class="draft-status-indicator draft-status-published">● %s</span>',
                    esc_html__('Published', 'draft-status-indexer')
                );
            } else {
                // Draft posts - check completion status from post meta
                $is_complete = get_post_meta($post_id, '_draft_complete', true);

                // Complete drafts - show green checkmark
                if ($is_complete === 'yes') {
                    printf(
                        '<span class="draft-status-indicator draft-status-complete">✓ %s</span>',
                        esc_html__('Complete', 'draft-status-indexer')
                    );
                } else {
                    // Incomplete drafts - show red X
                    printf(
                        '<span class="draft-status-indicator draft-status-incomplete">✗ %s</span>',
                        esc_html__('Incomplete', 'draft-status-indexer')
                    );
                }
            }
        }
    }

    /**
     * Make column sortable
     *
     * Registers the "Writing Status" column as sortable so users can click
     * the column header to sort posts by their completion status.
     *
     * @since 1.0.0
     * @param array $columns Existing sortable columns.
     * @return array Modified sortable columns array.
     */
    public function make_completion_sortable($columns) {
        // Register the draft_completion column as sortable
        $columns['draft_completion'] = 'draft_completion';
        return $columns;
    }

    /**
     * Handle sorting logic
     *
     * Modifies the main query to sort by the _draft_complete meta value
     * when the user clicks the "Writing Status" column header.
     *
     * @since 1.0.0
     * @param WP_Query $query The WordPress query object.
     */
    public function sort_by_completion($query) {
        // Only modify admin queries on the main query
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        // If sorting by draft_completion, sort by the meta value
        if ($query->get('orderby') === 'draft_completion') {
            $query->set('meta_key', '_draft_complete');
            $query->set('orderby', 'meta_value');
        }
    }

    /**
     * Add meta box to post editor
     *
     * Registers a meta box in the post editor sidebar where users can mark
     * drafts as complete or view the published status.
     *
     * @since 1.0.0
     */
    public function add_completion_meta_box() {
        add_meta_box(
            'draft_completion_box',                          // Meta box ID
            __('Completion Status', 'draft-status-indexer'), // Title
            array($this, 'render_completion_meta_box'),      // Callback function
            'post',                                           // Post type
            'side',                                           // Context (sidebar)
            'default'                                            // Priority
        );
    }

    /**
     * Render meta box content
     *
     * Displays the meta box content in the post editor.
     * For published posts: Shows a blue "Published" indicator.
     * For drafts: Shows a checkbox to mark the draft as complete.
     *
     * @since 1.0.0
     * @param WP_Post $post The current post object.
     */
    public function render_completion_meta_box($post) {
        // Add nonce field for security verification
        wp_nonce_field('draft_completion_nonce', 'draft_completion_nonce_field');

        // Get the current post status
        $post_status = get_post_status($post->ID);

        // Published posts - show read-only status indicator
        if ($post_status === 'publish') {
            ?>
            <p class="draft-status-metabox-published">
                <span class="draft-status-indicator draft-status-published">● <?php esc_html_e('Published', 'draft-status-indexer'); ?></span>
            </p>
            <?php
        } else {
            // Draft posts - show completion checkbox
            $is_complete = get_post_meta($post->ID, '_draft_complete', true);
            ?>
            <p>
                <label>
                    <input type="checkbox" name="draft_complete" value="yes" <?php checked($is_complete, 'yes'); ?>>
                    <?php esc_html_e('Complete', 'draft-status-indexer'); ?>
                </label>
            </p>
            <p class="description">
                <?php esc_html_e('Check when you\'ve finished writing this draft. This helps you sort and track your writing progress.', 'draft-status-indexer'); ?>
            </p>
            <?php
        }
    }

    /**
     * Save completion status
     *
     * Saves the draft completion status when a post is saved.
     * Includes security checks (nonce, capabilities, autosave) and input sanitization.
     *
     * Data is stored in post meta with key '_draft_complete' and value 'yes' or 'no'.
     *
     * @since 1.0.0
     * @param int $post_id The post ID being saved.
     */
    public function save_completion_status($post_id) {
        // Security check: Verify nonce to prevent CSRF attacks
        if (!isset($_POST['draft_completion_nonce_field']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['draft_completion_nonce_field'])), 'draft_completion_nonce')) {
            return;
        }

        // Don't save during autosave to avoid unnecessary database writes
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Permission check: Ensure user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Sanitize and save completion status
        if (isset($_POST['draft_complete'])) {
            // Sanitize input using WordPress functions
            $draft_complete = sanitize_text_field(wp_unslash($_POST['draft_complete']));

            // Whitelist validation: Only accept 'yes' as valid value, everything else is 'no'
            $value = ($draft_complete === 'yes') ? 'yes' : 'no';

            // Save to post meta with underscore prefix (hidden from custom fields UI)
            update_post_meta($post_id, '_draft_complete', $value);
        } else {
            // Checkbox not checked - save as 'no'
            update_post_meta($post_id, '_draft_complete', 'no');
        }
    }
}

/**
 * Initialize the plugin
 *
 * Creates a new instance of the DraftStatusIndexer class.
 * This is executed immediately when the plugin file is loaded.
 *
 * @since 1.0.0
 */
new DraftStatusIndexer();
