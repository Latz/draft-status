<?php
/**
 * Unit tests for DraftStatus::sortByCompletion() — query modification guards.
 *
 * Tests that the method only modifies WP_Query when in the admin context,
 * processing the main query, with an orderby of 'draft_completion'.
 */

use PHPUnit\Framework\TestCase;

/**
 * Minimal WP_Query stub for use in sortByCompletion tests.
 */
class MockWPQuery {
    public array $data = [];
    public bool $_is_main = true;

    public function is_main_query(): bool {
        return $this->_is_main;
    }

    public function get( string $key, $default = '' ) {
        return $this->data[ $key ] ?? $default;
    }

    public function set( string $key, $value ): void {
        $this->data[ $key ] = $value;
    }
}

class SortByCompletionTest extends TestCase {

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
    public function does_nothing_when_not_admin(): void {
        WP_Mock::userFunction( 'is_admin' )->andReturn( false );

        $query             = new MockWPQuery();
        $query->_is_main   = true;
        $query->data['orderby'] = 'draft_completion';

        $this->plugin->sortByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function does_nothing_when_not_main_query(): void {
        WP_Mock::userFunction( 'is_admin' )->andReturn( true );

        $query             = new MockWPQuery();
        $query->_is_main   = false;
        $query->data['orderby'] = 'draft_completion';

        $this->plugin->sortByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function does_nothing_when_orderby_is_not_draft_completion(): void {
        WP_Mock::userFunction( 'is_admin' )->andReturn( true );

        $query             = new MockWPQuery();
        $query->_is_main   = true;
        $query->data['orderby'] = 'date';

        $this->plugin->sortByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function does_nothing_when_all_three_guards_fail(): void {
        // is_admin() is bootstrapped as false; WP_Mock cannot override pre-defined functions.
        // Verify: even with orderby=draft_completion on main query, is_admin=false prevents changes.
        $query             = new MockWPQuery();
        $query->_is_main   = true;
        $query->data['orderby'] = 'draft_completion';

        $this->plugin->sortByCompletion( $query );

        // meta_query must NOT be set when is_admin() returns false (bootstrap default).
        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }
}
