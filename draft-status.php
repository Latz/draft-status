<?php
/**
 * Plugin Name: Draft Status
 * Plugin URI: https://github.com/yourusername/draft-status
 * Description: Mark draft posts by completion status (complete/incomplete) with priority levels
 * Version: 1.4.0
 * Author: Latz
 * Author URI: https://elektroelch.de
 * * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: draft-status
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
class DraftStatus {

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

        // Register meta field for REST API support
        add_action('init', array($this, 'register_meta_field'));
    }

    /**
     * Get valid priority values
     *
     * Returns an array of valid priority values for validation.
     *
     * @since 1.4.0
     * @return array Valid priority values.
     */
    private function get_valid_priorities() {
        return array('none', 'low', 'medium', 'high', 'urgent');
    }

    /**
     * Get priority labels
     *
     * Returns an array of priority labels for display (excluding 'none').
     *
     * @since 1.4.0
     * @return array Priority labels with keys as priority values and values as translated labels.
     */
    private function get_priority_labels() {
        return array(
            'low' => __('Low', 'draft-status'),
            'medium' => __('Medium', 'draft-status'),
            'high' => __('High', 'draft-status'),
            'urgent' => __('Urgent', 'draft-status')
        );
    }

    /**
     * Sanitize priority value
     *
     * Validates and sanitizes priority value for REST API.
     *
     * @since 1.4.0
     * @param string $value The priority value to sanitize.
     * @return string Sanitized priority value.
     */
    public function sanitize_priority_value($value) {
        if (in_array($value, $this->get_valid_priorities(), true)) {
            return $value;
        }
        return 'none';
    }

    /**
     * Render completion status indicator
     *
     * Displays completion status (complete/incomplete) for a draft.
     *
     * @since 1.4.0
     * @param string $is_complete The completion status ('yes' or 'no').
     */
    private function render_completion_status($is_complete) {
        if ($is_complete === 'yes') {
            printf(
                '<span class="draft-status-indicator draft-status-complete" role="status" aria-label="%s">✓ %s</span>',
                esc_attr__('Draft completion status: Complete', 'draft-status'),
                esc_html__('Complete', 'draft-status')
            );
        } else {
            printf(
                '<span class="draft-status-indicator draft-status-incomplete" role="status" aria-label="%s">✗ %s</span>',
                esc_attr__('Draft completion status: Incomplete', 'draft-status'),
                esc_html__('Incomplete', 'draft-status')
            );
        }
    }

    /**
     * Get due date display information
     *
     * Calculates and returns the CSS class and label for a due date.
     *
     * @since 1.4.0
     * @param string $due_date The due date in Y-m-d format.
     * @return array Array with 'class' and 'label' keys.
     */
    private function get_due_date_display($due_date) {
        $due_timestamp = strtotime($due_date);
        $today = strtotime('today');
        $days_diff = floor(($due_timestamp - $today) / (60 * 60 * 24));

        $date_class = 'draft-due-date';
        $date_label = '';

        if ($days_diff < 0) {
            // Overdue
            $date_class .= ' draft-due-overdue';
            $date_label = sprintf(
                esc_html__('Overdue: %s', 'draft-status'),
                date_i18n(get_option('date_format'), $due_timestamp)
            );
        } elseif ($days_diff === 0) {
            // Due today
            $date_class .= ' draft-due-today';
            $date_label = esc_html__('Due today', 'draft-status');
        } elseif ($days_diff <= 3) {
            // Due soon (within 3 days)
            $date_class .= ' draft-due-soon';
            $date_label = sprintf(
                esc_html__('Due: %s', 'draft-status'),
                date_i18n(get_option('date_format'), $due_timestamp)
            );
        } else {
            // Due later
            $date_label = sprintf(
                esc_html__('Due: %s', 'draft-status'),
                date_i18n(get_option('date_format'), $due_timestamp)
            );
        }

        return array(
            'class' => $date_class,
            'label' => $date_label
        );
    }

    /**
     * Render due date
     *
     * Displays the due date if set.
     *
     * @since 1.4.0
     * @param string $due_date The due date in Y-m-d format.
     */
    private function render_due_date($due_date) {
        if (empty($due_date)) {
            return;
        }

        $display = $this->get_due_date_display($due_date);
        printf(
            '<br><span class="%s">%s</span>',
            esc_attr($display['class']),
            esc_html($display['label'])
        );
    }

    /**
     * Render priority badge
     *
     * Displays the priority badge if set (and not 'none').
     *
     * @since 1.4.0
     * @param string $priority The priority value.
     */
    private function render_priority_badge($priority) {
        if (empty($priority) || $priority === 'none') {
            return;
        }

        $priority_labels = $this->get_priority_labels();
        $priority_label = isset($priority_labels[$priority]) ? $priority_labels[$priority] : '';

        if (!empty($priority_label)) {
            printf(
                '<br><span class="draft-priority draft-priority-%s">%s</span>',
                esc_attr($priority),
                esc_html($priority_label)
            );
        }
    }

    /**
     * Enqueue admin stylesheets and scripts
     *
     * Loads the plugin's CSS and JS files only on relevant admin pages to improve performance.
     * The assets are loaded on posts list, post editor, and dashboard pages.
     *
     * @since 1.0.0
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_styles($hook) {
        // Only load on relevant pages: edit.php (posts list), post.php (edit post),
        // post-new.php (new post), and index.php (dashboard)
        if ($hook !== 'edit.php' && $hook !== 'post.php' && $hook !== 'post-new.php' && $hook !== 'index.php') {
            return;
        }

        // Enqueue the plugin stylesheet with versioning for cache busting
        wp_enqueue_style(
            'draft-status',                      // Handle
            plugin_dir_url(__FILE__) . 'draft-status.css', // Source
            array(),                                      // Dependencies
            '1.5.0'                                      // Version
        );

        // Enqueue the plugin JavaScript for post editor pages
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script(
                'draft-status',                      // Handle
                plugin_dir_url(__FILE__) . 'draft-status.js', // Source
                array(),                                      // Dependencies
                '1.5.0',                                     // Version
                true                                          // Load in footer
            );
        }
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
        $columns['draft_completion'] = __('Writing Status', 'draft-status');
        return $columns;
    }

    /**
     * Display column content
     *
     * Outputs the status indicator (Complete/Incomplete) for draft posts with due date.
     * Complete drafts show green, incomplete show red. Published posts show blue.
     *
     * @since 1.0.0
     * @param string $column The column identifier.
     * @param int    $post_id The post ID for the current row.
     */
    public function display_completion_column($column, $post_id) {
        // Only process our custom column
        if ($column !== 'draft_completion') {
            return;
        }

        // Check if post is published
        if (get_post_status($post_id) === 'publish') {
            printf(
                '<span class="draft-status-indicator draft-status-published" role="status" aria-label="%s">● %s</span>',
                esc_attr__('Post status: Published', 'draft-status'),
                esc_html__('Published', 'draft-status')
            );
            return;
        }

        // Get post meta data
        $is_complete = get_post_meta($post_id, '_draft_complete', true);
        $due_date = get_post_meta($post_id, '_draft_due_date', true);
        $priority = get_post_meta($post_id, '_draft_priority', true);

        // Render completion status
        $this->render_completion_status($is_complete);

        // Render due date
        $this->render_due_date($due_date);

        // Render priority badge
        $this->render_priority_badge($priority);
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
     * Modifies the main query to sort by priority and completion status
     * when the user clicks the "Writing Status" column header.
     * Priority order: urgent > high > medium > low
     *
     * @since 1.0.0
     * @param WP_Query $query The WordPress query object.
     */
    public function sort_by_completion($query) {
        global $wpdb;

        // Only modify admin queries on the main query
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        // If sorting by draft_completion, sort by priority then completion status
        if ($query->get('orderby') === 'draft_completion') {
            $query->set('meta_query', array(
                'relation' => 'AND',
                'priority_clause' => array(
                    'key' => '_draft_priority',
                    'compare' => 'EXISTS'
                ),
                'completion_clause' => array(
                    'key' => '_draft_complete',
                    'compare' => 'EXISTS'
                )
            ));

            // Custom ordering: priority first (urgent, high, medium, low), then completion
            $order = $query->get('order', 'ASC');
            $query->set('orderby', array(
                'priority_clause' => $order,
                'completion_clause' => $order
            ));

            // Use filter to modify the orderby clause for proper priority ordering
            add_filter('posts_orderby', array($this, 'custom_priority_orderby'), 10, 2);
        }
    }

    /**
     * Custom orderby clause for priority sorting
     *
     * Implements proper priority ordering using CASE statement.
     *
     * @since 1.3.0
     * @param string $orderby The ORDER BY clause.
     * @param WP_Query $query The WordPress query object.
     * @return string Modified ORDER BY clause.
     */
    public function custom_priority_orderby($orderby, $query) {
        global $wpdb;

        if (!is_admin() || !$query->is_main_query() || $query->get('orderby') !== 'draft_completion') {
            return $orderby;
        }

        $order = $query->get('order', 'ASC');
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        // Create custom ORDER BY with CASE for priority values
        // Priority order: urgent > high > medium > low > none (or empty)
        $orderby = "
            CASE (SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND meta_key = '_draft_priority' LIMIT 1)
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                WHEN 'none' THEN 5
                ELSE 6
            END {$order},
            (SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND meta_key = '_draft_complete' LIMIT 1) {$order}
        ";

        // Remove this filter to prevent it from affecting other queries
        remove_filter('posts_orderby', array($this, 'custom_priority_orderby'), 10);

        return $orderby;
    }

    /**
     * Custom orderby clause for dashboard widget
     *
     * Sorts dashboard widget drafts by priority first, then by modified date.
     *
     * @since 1.4.0
     * @param string $orderby The ORDER BY clause.
     * @param WP_Query $query The WordPress query object.
     * @return string Modified ORDER BY clause.
     */
    public function dashboard_widget_orderby($orderby, $query) {
        global $wpdb;

        // Only apply to our dashboard widget queries
        if ($query->get('orderby') !== 'priority_then_modified') {
            return $orderby;
        }

        // Sort by priority (urgent first), then by modified date (newest first)
        $orderby = "
            CASE (SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND meta_key = '_draft_priority' LIMIT 1)
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                WHEN 'none' THEN 5
                ELSE 6
            END ASC,
            {$wpdb->posts}.post_modified DESC
        ";

        return $orderby;
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
            __('Completion Status', 'draft-status'), // Title
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
     * For drafts: Shows a checkbox to mark the draft as complete and a due date field.
     * For published posts: Shows nothing (meta box is hidden for published posts).
     *
     * @since 1.0.0
     * @param WP_Post $post The current post object.
     */
    public function render_completion_meta_box($post) {
        // Get the current post status
        $post_status = get_post_status($post->ID);

        // Show published status for published posts
        if ($post_status === 'publish') {
            ?>
            <p class="draft-status-metabox-published">
                <span class="draft-status-indicator draft-status-published" role="status">● <?php esc_html_e('Published', 'draft-status'); ?></span>
            </p>
            <p class="description">
                <?php esc_html_e('This post has been published.', 'draft-status'); ?>
            </p>
            <?php
            return;
        }

        // Add nonce field for security verification
        wp_nonce_field('draft_completion_nonce', 'draft_completion_nonce_field');

        // Draft posts - show completion button
        $is_complete = get_post_meta($post->ID, '_draft_complete', true);
        $due_date = get_post_meta($post->ID, '_draft_due_date', true);
        ?>
        <input type="hidden" id="draft_complete_hidden" name="draft_complete" value="<?php echo esc_attr($is_complete === 'yes' ? 'yes' : 'no'); ?>">
        <p>
            <button type="button" id="draft_complete_button" class="button button-large draft-complete-toggle <?php echo esc_attr($is_complete === 'yes' ? 'is-complete' : 'is-incomplete'); ?>" aria-describedby="draft_complete_description" aria-pressed="<?php echo esc_attr($is_complete === 'yes' ? 'true' : 'false'); ?>" data-complete-text="<?php echo esc_attr__('Complete', 'draft-status'); ?>" data-incomplete-text="<?php echo esc_attr__('Incomplete', 'draft-status'); ?>">
                <span class="draft-status-icon"><?php echo $is_complete === 'yes' ? '✓' : '✗'; ?></span>
                <span class="draft-status-text"><?php echo $is_complete === 'yes' ? esc_html__('Complete', 'draft-status') : esc_html__('Incomplete', 'draft-status'); ?></span>
            </button>
        </p>
        <p class="description" id="draft_complete_description">
            <?php esc_html_e('Click to toggle the completion status of this draft. This helps you sort and track your writing progress.', 'draft-status'); ?>
        </p>

        <hr style="margin: 15px 0;">

        <p>
            <label for="draft_due_date">
                <strong><?php esc_html_e('Due Date', 'draft-status'); ?></strong>
            </label>
        </p>
        <p>
            <input type="date"
                   id="draft_due_date"
                   name="draft_due_date"
                   value="<?php echo esc_attr($due_date); ?>"
                   style="width: 100%;">
        </p>
        <p class="description">
            <?php esc_html_e('Set a target completion date for this draft.', 'draft-status'); ?>
        </p>

        <hr style="margin: 15px 0;">

        <p>
            <label for="draft_priority">
                <strong><?php esc_html_e('Priority', 'draft-status'); ?></strong>
            </label>
        </p>
        <p>
            <?php
            $priority = get_post_meta($post->ID, '_draft_priority', true);
            if (empty($priority)) {
                $priority = 'none'; // Default priority
            }
            ?>
            <select id="draft_priority" name="draft_priority" style="width: 100%;">
                <option value="none" <?php selected($priority, 'none'); ?>><?php esc_html_e('None', 'draft-status'); ?></option>
                <option value="low" <?php selected($priority, 'low'); ?>><?php esc_html_e('Low', 'draft-status'); ?></option>
                <option value="medium" <?php selected($priority, 'medium'); ?>><?php esc_html_e('Medium', 'draft-status'); ?></option>
                <option value="high" <?php selected($priority, 'high'); ?>><?php esc_html_e('High', 'draft-status'); ?></option>
                <option value="urgent" <?php selected($priority, 'urgent'); ?>><?php esc_html_e('Urgent', 'draft-status'); ?></option>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e('Set the priority level for this draft.', 'draft-status'); ?>
        </p>
        <?php
    }

    /**
     * Save completion status, due date, and priority
     *
     * Saves the draft completion status, due date, and priority when a post is saved.
     * Includes security checks (nonce, capabilities, autosave) and input sanitization.
     *
     * Data is stored in post meta with keys '_draft_complete', '_draft_due_date', and '_draft_priority'.
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

        // Save due date
        if (isset($_POST['draft_due_date'])) {
            $due_date = sanitize_text_field(wp_unslash($_POST['draft_due_date']));

            // Validate date format (YYYY-MM-DD)
            if (!empty($due_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
                update_post_meta($post_id, '_draft_due_date', $due_date);
            } elseif (empty($due_date)) {
                // If date is empty, delete the meta
                delete_post_meta($post_id, '_draft_due_date');
            }
        }

        // Save priority
        if (isset($_POST['draft_priority'])) {
            $priority = sanitize_text_field(wp_unslash($_POST['draft_priority']));

            // Whitelist validation: Only accept valid priority values
            if (in_array($priority, $this->get_valid_priorities(), true)) {
                update_post_meta($post_id, '_draft_priority', $priority);
            } else {
                // Default to none if invalid value
                update_post_meta($post_id, '_draft_priority', 'none');
            }
        }
    }

    /**
     * Add filter dropdown to posts list
     *
     * Adds a dropdown filter above the posts list to filter by completion status.
     * Shows options: All, Complete, and Incomplete.
     *
     * @since 1.0.0
     * @param string $post_type The current post type.
     */
    public function add_completion_filter_dropdown($post_type) {
        // Only show on the posts list page
        if ($post_type !== 'post') {
            return;
        }

        // Get current filter value from URL
        $selected = isset($_GET['draft_completion_filter']) ? sanitize_text_field(wp_unslash($_GET['draft_completion_filter'])) : '';

        ?>
        <select name="draft_completion_filter" id="draft_completion_filter" aria-label="<?php esc_attr_e('Filter posts by completion status', 'draft-status'); ?>">
            <option value=""><?php esc_html_e('All Completion Status', 'draft-status'); ?></option>
            <option value="complete" <?php selected($selected, 'complete'); ?>><?php esc_html_e('Complete', 'draft-status'); ?></option>
            <option value="incomplete" <?php selected($selected, 'incomplete'); ?>><?php esc_html_e('Incomplete', 'draft-status'); ?></option>
        </select>

        <?php
        // Priority filter dropdown
        $priority_selected = isset($_GET['draft_priority_filter']) ? sanitize_text_field(wp_unslash($_GET['draft_priority_filter'])) : '';
        ?>
        <select name="draft_priority_filter">
            <option value=""><?php esc_html_e('All Priorities', 'draft-status'); ?></option>
            <option value="urgent" <?php selected($priority_selected, 'urgent'); ?>><?php esc_html_e('Urgent', 'draft-status'); ?></option>
            <option value="high" <?php selected($priority_selected, 'high'); ?>><?php esc_html_e('High', 'draft-status'); ?></option>
            <option value="medium" <?php selected($priority_selected, 'medium'); ?>><?php esc_html_e('Medium', 'draft-status'); ?></option>
            <option value="low" <?php selected($priority_selected, 'low'); ?>><?php esc_html_e('Low', 'draft-status'); ?></option>
            <option value="none" <?php selected($priority_selected, 'none'); ?>><?php esc_html_e('None', 'draft-status'); ?></option>
        </select>
        <?php
    }

    /**
     * Filter posts by completion status and priority
     *
     * Modifies the query to filter posts based on the selected completion status
     * and/or priority from the dropdown filters. Only shows draft posts when filtering.
     *
     * @since 1.0.0
     * @param WP_Query $query The WordPress query object.
     */
    public function filter_posts_by_completion($query) {
        global $pagenow;

        // Only modify admin queries on the posts list page
        if (!is_admin() || $pagenow !== 'edit.php') {
            return;
        }

        $has_completion_filter = isset($_GET['draft_completion_filter']);
        $has_priority_filter = isset($_GET['draft_priority_filter']);

        // If no filters are set, return
        if (!$has_completion_filter && !$has_priority_filter) {
            return;
        }

        $meta_query = array('relation' => 'AND');

        // Handle completion status filter
        if ($has_completion_filter) {
            $filter = sanitize_text_field(wp_unslash($_GET['draft_completion_filter']));

            if ($filter === 'complete') {
                $meta_query[] = array(
                    'key' => '_draft_complete',
                    'value' => 'yes',
                    'compare' => '='
                );
                $query->set('post_status', 'draft');
            } elseif ($filter === 'incomplete') {
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_draft_complete',
                        'value' => 'no',
                        'compare' => '='
                    ),
                    array(
                        'key' => '_draft_complete',
                        'compare' => 'NOT EXISTS'
                    )
                );
                $query->set('post_status', 'draft');
            }
        }

        // Handle priority filter
        if ($has_priority_filter) {
            $priority_filter = sanitize_text_field(wp_unslash($_GET['draft_priority_filter']));

            if (in_array($priority_filter, $this->get_valid_priorities(), true)) {
                $meta_query[] = array(
                    'key' => '_draft_priority',
                    'value' => $priority_filter,
                    'compare' => '='
                );
                if (!$has_completion_filter) {
                    $query->set('post_status', 'draft');
                }
            }
        }

        // Apply meta query if we have filters
        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Add dashboard widget
     *
     * Registers a dashboard widget that shows draft statistics.
     *
     * @since 1.0.0
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'draft_status_widget',                           // Widget ID
            __('Draft Writing Status', 'draft-status'), // Widget title
            array($this, 'render_dashboard_widget')          // Callback function
        );
    }

    /**
     * Render dashboard widget content
     *
     * Displays statistics about draft completion status on the dashboard.
     * Shows lists of incomplete and complete drafts with their titles.
     *
     * @since 1.0.0
     */
    public function render_dashboard_widget() {
        global $wpdb;

        // Add custom orderby filter for dashboard widget
        add_filter('posts_orderby', array($this, 'dashboard_widget_orderby'), 10, 2);

        // Query for incomplete drafts
        $incomplete_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_draft_complete',
                    'value' => 'no',
                    'compare' => '='
                ),
                array(
                    'key' => '_draft_complete',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'posts_per_page' => -1,
            'orderby' => 'priority_then_modified',
            'order' => 'ASC'
        ));

        // Query for complete drafts
        $complete_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'meta_key' => '_draft_complete',
            'meta_value' => 'yes',
            'posts_per_page' => -1,
            'orderby' => 'priority_then_modified',
            'order' => 'ASC'
        ));

        // Remove the filter after queries are done
        remove_filter('posts_orderby', array($this, 'dashboard_widget_orderby'), 10);

        ?>
        <div class="draft-status-widget">
            <?php if ($incomplete_query->have_posts()): ?>
                <section class="draft-status-section" aria-labelledby="draft-status-incomplete-title">
                    <h4 class="draft-status-section-title" id="draft-status-incomplete-title">
                        <span class="draft-status-indicator draft-status-incomplete" aria-hidden="true">✗</span>
                        <?php
                        printf(
                            esc_html__('Incomplete Drafts (%d)', 'draft-status'),
                            $incomplete_query->found_posts
                        );
                        ?>
                    </h4>
                    <ul class="draft-status-list">
                        <?php while ($incomplete_query->have_posts()): $incomplete_query->the_post();
                            $due_date = get_post_meta(get_the_ID(), '_draft_due_date', true);
                            $priority = get_post_meta(get_the_ID(), '_draft_priority', true);
                        ?>
                            <li class="draft-status-item">
                                <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="draft-status-item-link" aria-label="<?php echo esc_attr(sprintf(__('Edit incomplete draft: %s, last modified %s', 'draft-status'), get_the_title(), get_the_modified_date())); ?>">
                                    <div class="draft-status-title">
                                        <?php if (!empty($priority) && $priority !== 'none'): ?>
                                            <?php
                                            $priority_labels = $this->get_priority_labels();
                                            $priority_label = isset($priority_labels[$priority]) ? $priority_labels[$priority] : '';
                                            ?>
                                            <?php if (!empty($priority_label)): ?>
                                                <span class="draft-priority draft-priority-<?php echo esc_attr($priority); ?>">
                                                    <?php echo esc_html($priority_label); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php echo esc_html(get_the_title()); ?>
                                    </div>
                                    <div class="draft-status-meta">
                                        <?php
                                        printf(
                                            esc_html__('Modified: %s', 'draft-status'),
                                            get_the_modified_date()
                                        );

                                        // Show due date if set
                                        if (!empty($due_date)) {
                                            $due_timestamp = strtotime($due_date);
                                            $today = strtotime('today');
                                            $days_diff = floor(($due_timestamp - $today) / (60 * 60 * 24));

                                            echo ' • ';

                                            if ($days_diff < 0) {
                                                printf(
                                                    '<span class="draft-due-overdue">%s</span>',
                                                    sprintf(
                                                        esc_html__('Overdue: %s', 'draft-status'),
                                                        date_i18n(get_option('date_format'), $due_timestamp)
                                                    )
                                                );
                                            } elseif ($days_diff === 0) {
                                                printf(
                                                    '<span class="draft-due-today">%s</span>',
                                                    esc_html__('Due today', 'draft-status')
                                                );
                                            } elseif ($days_diff <= 3) {
                                                printf(
                                                    '<span class="draft-due-soon">%s</span>',
                                                    sprintf(
                                                        esc_html__('Due: %s', 'draft-status'),
                                                        date_i18n(get_option('date_format'), $due_timestamp)
                                                    )
                                                );
                                            } else {
                                                printf(
                                                    esc_html__('Due: %s', 'draft-status'),
                                                    date_i18n(get_option('date_format'), $due_timestamp)
                                                );
                                            }
                                        }
                                        ?>
                                    </div>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($complete_query->have_posts()): ?>
                <section class="draft-status-section" aria-labelledby="draft-status-complete-title">
                    <h4 class="draft-status-section-title" id="draft-status-complete-title">
                        <span class="draft-status-indicator draft-status-complete" aria-hidden="true">✓</span>
                        <?php
                        printf(
                            esc_html__('Complete Drafts Ready for Review (%d)', 'draft-status'),
                            $complete_query->found_posts
                        );
                        ?>
                    </h4>
                    <ul class="draft-status-list">
                        <?php while ($complete_query->have_posts()): $complete_query->the_post();
                            $due_date = get_post_meta(get_the_ID(), '_draft_due_date', true);
                            $priority = get_post_meta(get_the_ID(), '_draft_priority', true);
                        ?>
                            <li class="draft-status-item">
                                <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="draft-status-item-link" aria-label="<?php echo esc_attr(sprintf(__('Edit complete draft: %s, last modified %s', 'draft-status'), get_the_title(), get_the_modified_date())); ?>">
                                    <div class="draft-status-title">
                                        <?php if (!empty($priority) && $priority !== 'none'): ?>
                                            <?php
                                            $priority_labels = $this->get_priority_labels();
                                            $priority_label = isset($priority_labels[$priority]) ? $priority_labels[$priority] : '';
                                            ?>
                                            <?php if (!empty($priority_label)): ?>
                                                <span class="draft-priority draft-priority-<?php echo esc_attr($priority); ?>">
                                                    <?php echo esc_html($priority_label); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php echo esc_html(get_the_title()); ?>
                                    </div>
                                    <div class="draft-status-meta">
                                        <?php
                                        printf(
                                            esc_html__('Modified: %s', 'draft-status'),
                                            get_the_modified_date()
                                        );

                                        // Show due date if set
                                        if (!empty($due_date)) {
                                            echo ' • ';
                                            printf(
                                                esc_html__('Due: %s', 'draft-status'),
                                                date_i18n(get_option('date_format'), strtotime($due_date))
                                            );
                                        }
                                        ?>
                                    </div>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!$incomplete_query->have_posts() && !$complete_query->have_posts()): ?>
                <output><?php esc_html_e('No drafts found. Start writing!', 'draft-status'); ?></output>
            <?php endif; ?>

            <p class="draft-status-link">
                <a href="<?php echo esc_url(admin_url('edit.php?post_status=draft&post_type=post')); ?>" aria-label="<?php esc_attr_e('View all draft posts in the posts list', 'draft-status'); ?>">
                    <?php esc_html_e('View All Drafts →', 'draft-status'); ?>
                </a>
            </p>
        </div>
        <?php

        // Reset post data
        wp_reset_postdata();
    }

    /**
     * Register meta fields for REST API
     *
     * Registers the _draft_complete, _draft_due_date, and _draft_priority meta fields
     * with REST API support for Gutenberg block editor and headless WordPress usage.
     *
     * @since 1.2.0
     */
    public function register_meta_field() {
        // Register completion status field
        register_post_meta('post', '_draft_complete', array(
            'type' => 'string',
            'description' => __('Draft completion status', 'draft-status'),
            'single' => true,
            'show_in_rest' => true,
            'default' => 'no',
            'sanitize_callback' => function($value) {
                // Only allow 'yes' or 'no' values
                return ($value === 'yes') ? 'yes' : 'no';
            },
            'auth_callback' => function() {
                // Only allow users who can edit posts
                return current_user_can('edit_posts');
            }
        ));

        // Register due date field
        register_post_meta('post', '_draft_due_date', array(
            'type' => 'string',
            'description' => __('Draft due date', 'draft-status'),
            'single' => true,
            'show_in_rest' => true,
            'default' => '',
            'sanitize_callback' => function($value) {
                // Validate date format (YYYY-MM-DD) or empty string
                if (empty($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return $value;
                }
                return '';
            },
            'auth_callback' => function() {
                // Only allow users who can edit posts
                return current_user_can('edit_posts');
            }
        ));

        // Register priority field
        register_post_meta('post', '_draft_priority', array(
            'type' => 'string',
            'description' => __('Draft priority level', 'draft-status'),
            'single' => true,
            'show_in_rest' => true,
            'default' => 'none',
            'sanitize_callback' => array($this, 'sanitize_priority_value'),
            'auth_callback' => function() {
                // Only allow users who can edit posts
                return current_user_can('edit_posts');
            }
        ));
    }
}

/**
 * Initialize the plugin
 *
 * Creates a new instance of the DraftStatus class.
 * This is executed immediately when the plugin file is loaded.
 *
 * @since 1.0.0
 */
new DraftStatus();
