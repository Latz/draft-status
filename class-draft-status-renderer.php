<?php
/**
 * Draft Status Renderer
 *
 * Contains private rendering and data helper methods for the DraftStatus plugin.
 * Extended by the main DraftStatus class.
 *
 * @since 1.5.0
 */

// Prevent direct access to this file for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendering helpers for the Draft Status plugin
 *
 * @since 1.5.0
 */
class DraftStatusRenderer {

    /**
     * Get valid priority values
     *
     * Returns an array of valid priority values for validation.
     *
     * @since 1.4.0
     * @return array Valid priority values.
     */
    protected function getValidPriorities() {
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
    protected function getPriorityLabels() {
        return array(
            'low' => __('Low', 'draft-status'),
            'medium' => __('Medium', 'draft-status'),
            'high' => __('High', 'draft-status'),
            'urgent' => __('Urgent', 'draft-status')
        );
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
    protected function getDueDateDisplay($due_date) {
        $due_timestamp = strtotime($due_date);
        $today = strtotime('today');
        $days_diff = (int) floor(($due_timestamp - $today) / (60 * 60 * 24));

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
     * Render completion status indicator
     *
     * Displays completion status (complete/incomplete) for a draft.
     *
     * @since 1.4.0
     * @param string $is_complete The completion status ('yes' or 'no').
     */
    protected function renderCompletionStatus($is_complete) {
        if ($is_complete === 'yes') {
            printf(
                '<span class="draft-status-indicator draft-status-complete" aria-label="%s">✓ %s</span>',
                esc_attr__('Draft completion status: Complete', 'draft-status'),
                esc_html__('Complete', 'draft-status')
            );
        } else {
            printf(
                '<span class="draft-status-indicator draft-status-incomplete" aria-label="%s">✗ %s</span>',
                esc_attr__('Draft completion status: Incomplete', 'draft-status'),
                esc_html__('Incomplete', 'draft-status')
            );
        }
    }

    /**
     * Render due date
     *
     * Displays the due date if set.
     *
     * @since 1.4.0
     * @param string $due_date The due date in Y-m-d format.
     */
    protected function renderDueDate($due_date) {
        if (empty($due_date)) {
            return;
        }

        $display = $this->getDueDateDisplay($due_date);
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
    protected function renderPriorityBadge($priority) {
        if (empty($priority) || $priority === 'none') {
            return;
        }

        $priority_labels = $this->getPriorityLabels();
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
     * Save draft due date from POST data
     *
     * Handles validation and storage of the due date meta field.
     *
     * @since 1.4.0
     * @param int $post_id The post ID.
     */
    protected function saveDraftDueDate($post_id) {
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
    }

    /**
     * Save draft priority from POST data
     *
     * Handles validation and storage of the priority meta field.
     *
     * @since 1.4.0
     * @param int $post_id The post ID.
     */
    protected function saveDraftPriority($post_id) {
        // Save priority
        if (isset($_POST['draft_priority'])) {
            $priority = sanitize_text_field(wp_unslash($_POST['draft_priority']));

            // Whitelist validation: Only accept valid priority values
            if (in_array($priority, $this->getValidPriorities(), true)) {
                update_post_meta($post_id, '_draft_priority', $priority);
            } else {
                // Default to none if invalid value
                update_post_meta($post_id, '_draft_priority', 'none');
            }
        }
    }

    /**
     * Get dashboard widget queries
     *
     * Builds and returns both incomplete and complete draft queries with proper ordering.
     *
     * @since 1.4.0
     * @return array Array of [incomplete_query, complete_query] WP_Query objects.
     */
    protected function getDashboardQueries() {
        // Add custom orderby filter for dashboard widget
        add_filter('posts_orderby', array($this, 'dashboardWidgetOrderby'), 10, 2);

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
        remove_filter('posts_orderby', array($this, 'dashboardWidgetOrderby'), 10);

        return array($incomplete_query, $complete_query);
    }

    /**
     * Render incomplete drafts section
     *
     * Displays the incomplete drafts list with priority badges and due date information.
     *
     * @since 1.4.0
     * @param WP_Query $query The incomplete drafts query object.
     */
    protected function renderDashboardIncompletePosts($query) {
        if (!$query->have_posts()) {
            return;
        }
        ?>
        <section class="draft-status-section" aria-labelledby="draft-status-incomplete-title">
            <h4 class="draft-status-section-title" id="draft-status-incomplete-title">
                <span class="draft-status-indicator draft-status-incomplete" aria-hidden="true">✗</span>
                <?php
                printf(
                    esc_html__('Incomplete Drafts (%d)', 'draft-status'),
                    $query->found_posts
                );
                ?>
            </h4>
            <ul class="draft-status-list">
                <?php while ($query->have_posts()): $query->the_post();
                    $due_date = get_post_meta(get_the_ID(), '_draft_due_date', true);
                    $priority = get_post_meta(get_the_ID(), '_draft_priority', true);
                ?>
                    <li class="draft-status-item">
                        <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="draft-status-item-link" aria-label="<?php echo esc_attr(sprintf(__('Edit incomplete draft: %s, last modified %s', 'draft-status'), get_the_title(), get_the_modified_date())); ?>">
                            <div class="draft-status-title">
                                <?php if (!empty($priority) && $priority !== 'none'): ?>
                                    <?php
                                    $priority_labels = $this->getPriorityLabels();
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
                                    echo $this->getDueDateDisplay($due_date);
                                }
                                ?>
                            </div>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </section>
        <?php
    }

    /**
     * Render complete drafts section
     *
     * Displays the complete drafts list ready for review with priority badges and due dates.
     *
     * @since 1.4.0
     * @param WP_Query $query The complete drafts query object.
     */
    protected function renderDashboardCompletePosts($query) {
        if (!$query->have_posts()) {
            return;
        }
        ?>
        <section class="draft-status-section" aria-labelledby="draft-status-complete-title">
            <h4 class="draft-status-section-title" id="draft-status-complete-title">
                <span class="draft-status-indicator draft-status-complete" aria-hidden="true">✓</span>
                <?php
                printf(
                    esc_html__('Complete Drafts Ready for Review (%d)', 'draft-status'),
                    $query->found_posts
                );
                ?>
            </h4>
            <ul class="draft-status-list">
                <?php while ($query->have_posts()): $query->the_post();
                    $due_date = get_post_meta(get_the_ID(), '_draft_due_date', true);
                    $priority = get_post_meta(get_the_ID(), '_draft_priority', true);
                ?>
                    <li class="draft-status-item">
                        <a href="<?php echo esc_url(get_edit_post_link(get_the_ID())); ?>" class="draft-status-item-link" aria-label="<?php echo esc_attr(sprintf(__('Edit complete draft: %s, last modified %s', 'draft-status'), get_the_title(), get_the_modified_date())); ?>">
                            <div class="draft-status-title">
                                <?php if (!empty($priority) && $priority !== 'none'): ?>
                                    <?php
                                    $priority_labels = $this->getPriorityLabels();
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
        <?php
    }
}
