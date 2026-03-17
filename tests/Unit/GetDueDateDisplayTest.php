<?php
/**
 * Unit tests for DraftStatus::getDueDateDisplay() via renderDueDate() output.
 *
 * getDueDateDisplay() is private, so we test it through renderDueDate() by
 * capturing output. The four states under test:
 *   - overdue  (date in the past)
 *   - due today
 *   - due soon (within 3 days)
 *   - due later (more than 3 days away)
 */

use PHPUnit\Framework\TestCase;

class GetDueDateDisplayTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    /** @var ReflectionMethod */
    private $method;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();

        // Access getDueDateDisplay() directly via reflection since it is private.
        $ref          = new ReflectionClass( DraftStatus::class );
        $this->method = $ref->getMethod( 'getDueDateDisplay' );
        $this->method->setAccessible( true );
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    private function call( string $date ): array {
        return $this->method->invoke( $this->plugin, $date );
    }

    /** @test */
    public function overdue_date_returns_overdue_css_class(): void {
        $yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
        $result    = $this->call( $yesterday );

        $this->assertStringContainsString( 'draft-due-overdue', $result['class'] );
    }

    /** @test */
    public function overdue_date_label_contains_overdue_text(): void {
        $yesterday = date( 'Y-m-d', strtotime( '-7 days' ) );
        $result    = $this->call( $yesterday );

        $this->assertStringContainsString( 'Overdue', $result['label'] );
    }

    /** @test */
    public function today_returns_due_today_css_class(): void {
        $today  = date( 'Y-m-d' );
        $result = $this->call( $today );

        $this->assertStringContainsString( 'draft-due-today', $result['class'] );
    }

    /** @test */
    public function today_label_says_due_today(): void {
        $today  = date( 'Y-m-d' );
        $result = $this->call( $today );

        $this->assertSame( 'Due today', $result['label'] );
    }

    /** @test */
    public function three_days_away_returns_due_soon_css_class(): void {
        $soon   = date( 'Y-m-d', strtotime( '+3 days' ) );
        $result = $this->call( $soon );

        $this->assertStringContainsString( 'draft-due-soon', $result['class'] );
    }

    /** @test */
    public function one_day_away_returns_due_soon_css_class(): void {
        $tomorrow = date( 'Y-m-d', strtotime( '+1 day' ) );
        $result   = $this->call( $tomorrow );

        $this->assertStringContainsString( 'draft-due-soon', $result['class'] );
    }

    /** @test */
    public function four_days_away_returns_base_class_only(): void {
        $later  = date( 'Y-m-d', strtotime( '+4 days' ) );
        $result = $this->call( $later );

        $this->assertStringNotContainsString( 'draft-due-overdue', $result['class'] );
        $this->assertStringNotContainsString( 'draft-due-today', $result['class'] );
        $this->assertStringNotContainsString( 'draft-due-soon', $result['class'] );
        $this->assertStringContainsString( 'draft-due-date', $result['class'] );
    }

    /** @test */
    public function four_days_away_label_contains_due_prefix(): void {
        $later  = date( 'Y-m-d', strtotime( '+4 days' ) );
        $result = $this->call( $later );

        $this->assertStringContainsString( 'Due:', $result['label'] );
    }

    /** @test */
    public function result_always_contains_class_and_label_keys(): void {
        foreach ( [ '-5 days', 'today', '+2 days', '+10 days' ] as $offset ) {
            $date   = date( 'Y-m-d', strtotime( $offset ) );
            $result = $this->call( $date );

            $this->assertArrayHasKey( 'class', $result, "Missing 'class' for offset: $offset" );
            $this->assertArrayHasKey( 'label', $result, "Missing 'label' for offset: $offset" );
        }
    }
}
