<?php
/**
 * Unit tests for DraftStatus::filterPostsByCompletion() and its private helpers.
 *
 * The public method is tested via the is_admin() guard (returns false in
 * bootstrap). Private helpers are exercised directly via ReflectionMethod.
 */

use PHPUnit\Framework\TestCase;

class MockWPQueryFilter {
    public array $data = [];
    public bool $_is_main = true;
    public function is_main_query(): bool { return $this->_is_main; }
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
}

class FilterPostsByCompletionUnitTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        unset( $_GET['draft_completion_filter'], $_GET['draft_priority_filter'] );
    }

    /** @test */
    public function returns_early_when_not_admin(): void {
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['draft_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function apply_completion_filter_complete_sets_meta_query(): void {
        $_GET['draft_completion_filter'] = 'complete';

        $method = new ReflectionMethod( DraftStatus::class, 'applyCompletionFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query ] );

        $this->assertCount( 2, $filter_meta_query );
        $this->assertSame( 'draft', $query->get( 'post_status' ) );
    }

    /** @test */
    public function apply_completion_filter_incomplete_sets_or_meta_query(): void {
        $_GET['draft_completion_filter'] = 'incomplete';

        $method = new ReflectionMethod( DraftStatus::class, 'applyCompletionFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query ] );

        $this->assertCount( 2, $filter_meta_query );
        $this->assertSame( 'draft', $query->get( 'post_status' ) );
    }

    /** @test */
    public function apply_completion_filter_unknown_value_does_nothing(): void {
        $_GET['draft_completion_filter'] = 'unknown';

        $method = new ReflectionMethod( DraftStatus::class, 'applyCompletionFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query ] );

        $this->assertCount( 1, $filter_meta_query );
    }

    /** @test */
    public function apply_priority_filter_valid_priority_adds_clause(): void {
        $_GET['draft_priority_filter'] = 'high';

        $method = new ReflectionMethod( DraftStatus::class, 'applyPriorityFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];
        $has_completion_filter = false;

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query, $has_completion_filter ] );

        $this->assertCount( 2, $filter_meta_query );
        $this->assertSame( 'draft', $query->get( 'post_status' ) );
    }

    /** @test */
    public function apply_priority_filter_invalid_priority_does_nothing(): void {
        $_GET['draft_priority_filter'] = 'invalid';

        $method = new ReflectionMethod( DraftStatus::class, 'applyPriorityFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];
        $has_completion_filter = false;

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query, $has_completion_filter ] );

        $this->assertCount( 1, $filter_meta_query );
    }

    /** @test */
    public function apply_priority_filter_with_completion_filter_does_not_set_post_status(): void {
        $_GET['draft_priority_filter'] = 'high';

        $method = new ReflectionMethod( DraftStatus::class, 'applyPriorityFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];
        $has_completion_filter = true;

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query, $has_completion_filter ] );

        $this->assertArrayNotHasKey( 'post_status', $query->data );
    }
}
