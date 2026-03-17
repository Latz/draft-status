<?php
/**
 * Unit tests for DraftStatus::getValidPriorities(), getPriorityLabels(),
 * and registerMetaField().
 *
 * These tests run entirely with WP_Mock — no database required.
 */

use PHPUnit\Framework\TestCase;

class GetValidPrioritiesTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    // -------------------------------------------------------------------------
    // getValidPriorities
    // -------------------------------------------------------------------------

    /** @test */
    public function returns_array_with_five_priorities(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'getValidPriorities' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertCount( 5, $result );
    }

    /** @test */
    public function contains_all_expected_priorities(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'getValidPriorities' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        foreach ( [ 'none', 'low', 'medium', 'high', 'urgent' ] as $priority ) {
            $this->assertContains( $priority, $result );
        }
    }

    /** @test */
    public function urgent_is_in_valid_priorities(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'getValidPriorities' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertTrue( in_array( 'urgent', $result, true ) );
    }

    // -------------------------------------------------------------------------
    // getPriorityLabels
    // -------------------------------------------------------------------------

    /** @test */
    public function returns_four_labels(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'getPriorityLabels' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertCount( 4, $result );
    }

    /** @test */
    public function does_not_contain_none_key(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'getPriorityLabels' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertFalse( array_key_exists( 'none', $result ) );
    }

    /** @test */
    public function contains_high_key(): void {
        $method = new ReflectionMethod( DraftStatus::class, 'getPriorityLabels' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertTrue( array_key_exists( 'high', $result ) );
    }

    // -------------------------------------------------------------------------
    // registerMetaField
    // -------------------------------------------------------------------------

    /** @test */
    public function register_meta_field_executes_without_error(): void {
        $this->plugin->registerMetaField();

        $this->assertTrue( true );
    }
}
