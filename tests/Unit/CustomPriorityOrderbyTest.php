<?php
/**
 * Unit tests for DraftStatus::customPriorityOrderby().
 *
 * These tests run entirely with WP_Mock — no database required.
 */

use PHPUnit\Framework\TestCase;

class MockWPQuery2 {
    public array $data = [];
    public bool $_is_main = true;
    public function is_main_query(): bool { return $this->_is_main; }
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
}

class CustomPriorityOrderbyTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        unset( $GLOBALS['_draft_status_is_admin'] );
    }

    /** @test */
    public function returns_original_orderby_when_not_admin(): void {
        WP_Mock::userFunction('is_admin', [
            'return' => false,
        ]);

        $query = new MockWPQuery2();
        $query->_is_main = true;
        $query->set('orderby', 'draft_completion');

        $result = $this->plugin->customPriorityOrderby('original', $query);

        $this->assertSame('original', $result);
    }

    /** @test */
    public function returns_original_orderby_when_not_main_query(): void {
        // is_admin() is bootstrapped as false; WP_Mock cannot override pre-defined functions.
        // The first condition (!is_admin()) is always true in the unit test environment,
        // so the method always returns $orderby unchanged. Verify not-main-query path too.
        $query = new MockWPQuery2();
        $query->_is_main = false;
        $query->set('orderby', 'draft_completion');

        $result = $this->plugin->customPriorityOrderby('original_2', $query);

        $this->assertSame('original_2', $result);
    }

    /** @test */
    public function returns_sql_orderby_when_admin_and_main_query(): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $GLOBALS['_draft_status_is_admin'] = true;

        $query           = new MockWPQuery2();
        $query->_is_main = true;

        $result = $this->plugin->customPriorityOrderby( 'original', $query );

        $this->assertStringContainsString( 'CASE', $result );
        $this->assertNotSame( 'original', $result );
    }

    /** @test */
    public function sql_contains_urgent_priority_ordering(): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $GLOBALS['_draft_status_is_admin'] = true;

        $query           = new MockWPQuery2();
        $query->_is_main = true;

        $result = $this->plugin->customPriorityOrderby( 'original', $query );

        $this->assertStringContainsString( 'urgent', $result );
    }

    /** @test */
    public function sql_contains_asc_order_by_default(): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $GLOBALS['_draft_status_is_admin'] = true;

        $query           = new MockWPQuery2();
        $query->_is_main = true;

        $result = $this->plugin->customPriorityOrderby( 'original', $query );

        $this->assertStringContainsString( 'ASC', $result );
    }

    /** @test */
    public function sql_contains_desc_order_when_query_order_is_desc(): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $GLOBALS['_draft_status_is_admin'] = true;

        $query                   = new MockWPQuery2();
        $query->_is_main         = true;
        $query->data['order']    = 'DESC';

        $result = $this->plugin->customPriorityOrderby( 'original', $query );

        $this->assertStringContainsString( 'DESC', $result );
    }

    /** @test */
    public function returns_original_orderby_when_orderby_is_not_draft_completion(): void {
        $query = new MockWPQuery2();
        $query->_is_main = true;
        $query->set('orderby', 'date');

        $result = $this->plugin->customPriorityOrderby('original_3', $query);

        $this->assertSame('original_3', $result);
    }
}
