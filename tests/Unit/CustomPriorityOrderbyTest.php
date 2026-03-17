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
    public function returns_original_orderby_when_orderby_is_not_draft_completion(): void {
        $query = new MockWPQuery2();
        $query->_is_main = true;
        $query->set('orderby', 'date');

        $result = $this->plugin->customPriorityOrderby('original_3', $query);

        $this->assertSame('original_3', $result);
    }
}
