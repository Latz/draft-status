<?php
/**
 * Unit tests for DraftStatusRenderer::renderDueDate() — output guard and span rendering.
 *
 * Tests that the method produces no output for empty input and emits a correctly
 * classed <span> for future and overdue dates.
 */

use PHPUnit\Framework\TestCase;

class RenderDueDateTest extends TestCase {

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
    public function empty_due_date_produces_no_output(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'renderDueDate' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, '' );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    /** @test */
    public function non_empty_due_date_outputs_span(): void {
        $future_date = date( 'Y-m-d', strtotime( '+10 days' ) );

        WP_Mock::userFunction( 'current_time' )->andReturn( time() );
        WP_Mock::userFunction( 'get_option' )->andReturn( 'Y-m-d' );
        WP_Mock::userFunction( 'date_i18n' )->andReturn( '2099-01-01' );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderDueDate' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, $future_date );
        $output = ob_get_clean();

        $this->assertStringContainsString( '<span', $output );
    }

    /** @test */
    public function future_date_span_has_due_date_class(): void {
        $future_date = date( 'Y-m-d', strtotime( '+10 days' ) );

        WP_Mock::userFunction( 'current_time' )->andReturn( time() );
        WP_Mock::userFunction( 'get_option' )->andReturn( 'Y-m-d' );
        WP_Mock::userFunction( 'date_i18n' )->andReturn( '2099-01-01' );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderDueDate' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, $future_date );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-due-date', $output );
    }

    /** @test */
    public function overdue_date_span_has_overdue_class(): void {
        WP_Mock::userFunction( 'current_time' )->andReturn( time() );
        WP_Mock::userFunction( 'get_option' )->andReturn( 'Y-m-d' );
        WP_Mock::userFunction( 'date_i18n' )->andReturn( '2000-01-01' );
        WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );

        $method = new ReflectionMethod( DraftStatus::class, 'renderDueDate' );
        $method->setAccessible( true );

        ob_start();
        $method->invoke( $this->plugin, '2000-01-01' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft-due-overdue', $output );
    }
}
