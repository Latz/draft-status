<?php
/**
 * Unit tests for DraftStatusRenderer::renderPriorityBadge() and
 * DraftStatusRenderer::renderPriorityBadgeForDashboard().
 *
 * Both methods are non-public, so they are accessed via ReflectionMethod.
 * Output is captured with ob_start() / ob_get_clean().
 */

use PHPUnit\Framework\TestCase;

class RenderPriorityBadgeTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function callRenderPriorityBadge( string $priority ): string {
        $ref    = new ReflectionClass( DraftStatus::class );
        $method = $ref->getMethod( 'renderPriorityBadge' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, $priority );
        return ob_get_clean();
    }

    private function callRenderPriorityBadgeForDashboard( string $priority ): string {
        $ref    = new ReflectionClass( DraftStatus::class );
        $method = $ref->getMethod( 'renderPriorityBadgeForDashboard' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, $priority );
        return ob_get_clean();
    }

    // -----------------------------------------------------------------------
    // renderPriorityBadge tests
    // -----------------------------------------------------------------------

    /** @test */
    public function empty_priority_produces_no_output(): void {
        $output = $this->callRenderPriorityBadge( '' );

        $this->assertSame( '', $output );
    }

    /** @test */
    public function none_priority_produces_no_output(): void {
        $output = $this->callRenderPriorityBadge( 'none' );

        $this->assertSame( '', $output );
    }

    /** @test */
    public function valid_priority_outputs_badge_span(): void {
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );

        $output = $this->callRenderPriorityBadge( 'high' );

        $this->assertStringContainsString( 'draft-priority-high', $output );
    }

    /** @test */
    public function unknown_priority_produces_no_output(): void {
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );

        $output = $this->callRenderPriorityBadge( 'invalid' );

        $this->assertSame( '', $output );
    }

    // -----------------------------------------------------------------------
    // renderPriorityBadgeForDashboard tests
    // -----------------------------------------------------------------------

    /** @test */
    public function dashboard_badge_has_no_br_tag(): void {
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );

        $output = $this->callRenderPriorityBadgeForDashboard( 'urgent' );

        $this->assertStringNotContainsString( '<br>', $output );
    }

    /** @test */
    public function dashboard_badge_outputs_span(): void {
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );

        $output = $this->callRenderPriorityBadgeForDashboard( 'low' );

        $this->assertStringContainsString( 'draft-priority-low', $output );
    }
}
