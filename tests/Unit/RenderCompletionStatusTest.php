<?php
/**
 * Unit tests for DraftStatusRenderer::renderCompletionStatus() — output verification.
 *
 * Tests that the method outputs the correct HTML span for both complete
 * and incomplete states, including CSS classes and Unicode symbols.
 */

use PHPUnit\Framework\TestCase;

class RenderCompletionStatusTest extends TestCase {

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
    public function complete_status_outputs_complete_span(): void {
        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderCompletionStatus' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, 'yes' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-status-complete', $output );
    }

    /** @test */
    public function complete_status_outputs_checkmark(): void {
        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderCompletionStatus' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, 'yes' );
        $output = ob_get_clean();

        $this->assertStringContainsString( '✓', $output );
    }

    /** @test */
    public function incomplete_status_outputs_incomplete_span(): void {
        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderCompletionStatus' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, 'no' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-status-incomplete', $output );
    }

    /** @test */
    public function incomplete_status_outputs_x_symbol(): void {
        WP_Mock::userFunction( 'esc_attr__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderCompletionStatus' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, 'no' );
        $output = ob_get_clean();

        $this->assertStringContainsString( '✗', $output );
    }
}
