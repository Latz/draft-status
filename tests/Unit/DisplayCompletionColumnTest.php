<?php
/**
 * Unit tests for DraftStatus::displayCompletionColumn().
 */

use PHPUnit\Framework\TestCase;

class DisplayCompletionColumnTest extends TestCase {

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
    public function wrong_column_produces_no_output(): void {
        ob_start();
        $this->plugin->displayCompletionColumn( 'title', 1 );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    /** @test */
    public function draft_column_calls_completion_status_render(): void {
        // get_post_status is stubbed in bootstrap to return 'draft', which is what we need.
        // get_post_meta is stubbed in bootstrap to return '' for single lookups.
        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        ob_start();
        $this->plugin->displayCompletionColumn( 'draft_completion', 1 );
        $output = ob_get_clean();

        // Bootstrap stubs get_post_status='draft', get_post_meta='' so is_complete=''.
        // Empty string !== 'yes' → incomplete branch fires.
        $this->assertStringContainsString( 'draft-status-incomplete', $output );
    }

    /** @test */
    public function draft_complete_post_shows_complete_span(): void {
        // get_post_status bootstrap stub returns 'draft' — correct for this test.
        // Override get_post_meta via WP_Mock to return 'yes' for _draft_complete.
        // Note: bootstrap defines get_post_meta as a real function, so WP_Mock
        // cannot override it. Instead we call renderCompletionStatus directly
        // via reflection to verify the 'complete' path.
        $method = new ReflectionMethod( DraftStatus::class, 'renderCompletionStatus' );
        $method->setAccessible( true );

        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        ob_start();
        $method->invoke( $this->plugin, 'yes' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-status-complete', $output );
    }

    /** @test */
    public function draft_with_priority_shows_priority_badge(): void {
        // Test renderPriorityBadge directly (bootstrap can't be overridden for get_post_meta).
        $method = new ReflectionMethod( DraftStatus::class, 'renderPriorityBadge' );
        $method->setAccessible( true );

        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );

        ob_start();
        $method->invoke( $this->plugin, 'high' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-priority-high', $output );
    }

    /** @test */
    public function draft_incomplete_shows_incomplete_span(): void {
        // get_post_status bootstrap stub returns 'draft'.
        // get_post_meta bootstrap stub returns '' for single lookups.
        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        ob_start();
        $this->plugin->displayCompletionColumn( 'draft_completion', 1 );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-status-incomplete', $output );
    }
}
