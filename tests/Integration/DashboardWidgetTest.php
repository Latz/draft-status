<?php
/**
 * Integration tests for DraftStatus dashboard widget methods.
 *
 * Covers getDashboardQueries(), renderDashboardWidget(),
 * renderDashboardIncompletePosts(), and renderDashboardCompletePosts()
 * against a real WordPress test database.
 */

class DashboardWidgetTest extends WP_UnitTestCase {

    /** @var DraftStatus */
    private $plugin;

    /** @var int[] Post IDs created for tests */
    private $post_ids = [];

    public function setUp(): void {
        parent::setUp();

        $this->plugin = new DraftStatus();

        // incomplete_urgent: draft, _draft_complete=no, _draft_priority=urgent
        $this->post_ids['incomplete_urgent'] = self::factory()->post->create( [
            'post_status' => 'draft',
            'post_title'  => 'Urgent Draft',
        ] );
        update_post_meta( $this->post_ids['incomplete_urgent'], '_draft_complete', 'no' );
        update_post_meta( $this->post_ids['incomplete_urgent'], '_draft_priority', 'urgent' );

        // complete_high: draft, _draft_complete=yes, _draft_priority=high
        $this->post_ids['complete_high'] = self::factory()->post->create( [
            'post_status' => 'draft',
            'post_title'  => 'Complete Draft',
        ] );
        update_post_meta( $this->post_ids['complete_high'], '_draft_complete', 'yes' );
        update_post_meta( $this->post_ids['complete_high'], '_draft_priority', 'high' );

        // incomplete_no_meta: draft, no meta at all
        $this->post_ids['incomplete_no_meta'] = self::factory()->post->create( [
            'post_status' => 'draft',
            'post_title'  => 'No Meta Draft',
        ] );
    }

    // -----------------------------------------------------------------------
    // Helper: invoke getDashboardQueries() via reflection
    // -----------------------------------------------------------------------

    private function callGetDashboardQueries(): array {
        $method = new ReflectionMethod( DraftStatus::class, 'getDashboardQueries' );
        $method->setAccessible( true );
        return $method->invoke( $this->plugin );
    }

    // -----------------------------------------------------------------------
    // 1. getDashboardQueries returns two WP_Query objects
    // -----------------------------------------------------------------------

    /** @test */
    public function get_dashboard_queries_returns_two_queries(): void {
        $result = $this->callGetDashboardQueries();

        $this->assertIsArray( $result );
        $this->assertCount( 2, $result );
        $this->assertInstanceOf( WP_Query::class, $result[0] );
        $this->assertInstanceOf( WP_Query::class, $result[1] );
    }

    // -----------------------------------------------------------------------
    // 2. Incomplete query finds incomplete posts (and excludes complete ones)
    // -----------------------------------------------------------------------

    /** @test */
    public function incomplete_query_finds_incomplete_posts(): void {
        $result     = $this->callGetDashboardQueries();
        $incomplete = $result[0];

        $this->assertTrue( $incomplete->have_posts() );

        $ids = wp_list_pluck( $incomplete->posts, 'ID' );

        $this->assertContains( $this->post_ids['incomplete_urgent'],  $ids );
        $this->assertContains( $this->post_ids['incomplete_no_meta'], $ids );
        $this->assertNotContains( $this->post_ids['complete_high'],   $ids );
    }

    // -----------------------------------------------------------------------
    // 3. Complete query finds complete posts (and excludes incomplete ones)
    // -----------------------------------------------------------------------

    /** @test */
    public function complete_query_finds_complete_posts(): void {
        $result   = $this->callGetDashboardQueries();
        $complete = $result[1];

        $this->assertTrue( $complete->have_posts() );

        $ids = wp_list_pluck( $complete->posts, 'ID' );

        $this->assertContains( $this->post_ids['complete_high'],          $ids );
        $this->assertNotContains( $this->post_ids['incomplete_urgent'],   $ids );
        $this->assertNotContains( $this->post_ids['incomplete_no_meta'],  $ids );
    }

    // -----------------------------------------------------------------------
    // 4. renderDashboardWidget outputs wrapper div
    // -----------------------------------------------------------------------

    /** @test */
    public function render_dashboard_widget_outputs_wrapper_div(): void {
        ob_start();
        $this->plugin->renderDashboardWidget();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-status-widget', $output );
    }

    // -----------------------------------------------------------------------
    // 5. renderDashboardWidget outputs "View All Drafts" link
    // -----------------------------------------------------------------------

    /** @test */
    public function render_dashboard_widget_outputs_view_all_link(): void {
        ob_start();
        $this->plugin->renderDashboardWidget();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'View All Drafts', $output );
    }

    // -----------------------------------------------------------------------
    // 6. renderDashboardIncompletePosts outputs ✗ symbol and section class
    // -----------------------------------------------------------------------

    /** @test */
    public function render_incomplete_posts_outputs_x_symbol(): void {
        $result          = $this->callGetDashboardQueries();
        $incomplete_query = $result[0];

        $method = new ReflectionMethod( DraftStatus::class, 'renderDashboardIncompletePosts' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, $incomplete_query );
        $output = ob_get_clean();

        $this->assertStringContainsString( '✗', $output );
        $this->assertStringContainsString( 'draft-status-incomplete', $output );
    }

    // -----------------------------------------------------------------------
    // 7. renderDashboardCompletePosts outputs ✓ symbol and section class
    // -----------------------------------------------------------------------

    /** @test */
    public function render_complete_posts_outputs_checkmark(): void {
        $result         = $this->callGetDashboardQueries();
        $complete_query = $result[1];

        $method = new ReflectionMethod( DraftStatus::class, 'renderDashboardCompletePosts' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, $complete_query );
        $output = ob_get_clean();

        $this->assertStringContainsString( '✓', $output );
        $this->assertStringContainsString( 'draft-status-complete', $output );
    }
}
