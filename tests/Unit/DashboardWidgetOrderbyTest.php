<?php
/**
 * Unit tests for DraftStatus::dashboardWidgetOrderby().
 */

use PHPUnit\Framework\TestCase;

class MockWPQuery3 {
    public array $data = [];
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
    public function is_main_query(): bool { return true; }
}

class DashboardWidgetOrderbyTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    public function test_returns_original_orderby_when_not_priority_then_modified(): void {
        $query = new MockWPQuery3();
        $query->set('orderby', 'date');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        $this->assertSame('original', $result);
    }

    public function test_returns_sql_when_orderby_is_priority_then_modified(): void {
        global $wpdb;
        $wpdb = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts = 'wp_posts';

        $query = new MockWPQuery3();
        $query->set('orderby', 'priority_then_modified');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        $this->assertStringContainsString('CASE', $result);
    }

    public function test_sql_orders_urgent_first(): void {
        global $wpdb;
        $wpdb = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts = 'wp_posts';

        $query = new MockWPQuery3();
        $query->set('orderby', 'priority_then_modified');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        $this->assertStringContainsString("WHEN 'urgent' THEN 1", $result);
    }

    public function test_sql_orders_by_modified_date_desc(): void {
        global $wpdb;
        $wpdb = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts = 'wp_posts';

        $query = new MockWPQuery3();
        $query->set('orderby', 'priority_then_modified');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        $this->assertStringContainsString('post_modified DESC', $result);
    }
}
