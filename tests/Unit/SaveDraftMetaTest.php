<?php
/**
 * Unit tests for DraftStatus::saveDraftDueDate() and DraftStatus::saveDraftPriority().
 *
 * Both methods are protected. They are exercised via ReflectionMethod.
 *
 * update_post_meta, delete_post_meta, sanitize_text_field, and wp_unslash are
 * defined as real no-op stubs in bootstrap.php. WP_Mock cannot override them,
 * so these tests assert that each code path executes without error rather than
 * verifying call arguments.
 */

use PHPUnit\Framework\TestCase;

class SaveDraftMetaTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    /** @var \ReflectionMethod */
    private $saveDraftDueDate;

    /** @var \ReflectionMethod */
    private $saveDraftPriority;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();

        $this->saveDraftDueDate = new \ReflectionMethod( DraftStatus::class, 'saveDraftDueDate' );
        $this->saveDraftDueDate->setAccessible( true );

        $this->saveDraftPriority = new \ReflectionMethod( DraftStatus::class, 'saveDraftPriority' );
        $this->saveDraftPriority->setAccessible( true );
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        unset( $_POST['draft_due_date'], $_POST['draft_priority'] );
    }

    // -------------------------------------------------------------------------
    // saveDraftDueDate
    // -------------------------------------------------------------------------

    /** @test */
    public function save_due_date_with_valid_date_executes_without_error(): void {
        $_POST['draft_due_date'] = '2026-12-31';

        $this->saveDraftDueDate->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    /** @test */
    public function save_due_date_with_empty_string_executes_without_error(): void {
        $_POST['draft_due_date'] = '';

        $this->saveDraftDueDate->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    /** @test */
    public function save_due_date_with_invalid_format_executes_without_error(): void {
        $_POST['draft_due_date'] = 'not-a-date';

        $this->saveDraftDueDate->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    /** @test */
    public function save_due_date_without_post_key_executes_without_error(): void {
        // $_POST['draft_due_date'] is intentionally not set.

        $this->saveDraftDueDate->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    // -------------------------------------------------------------------------
    // saveDraftPriority
    // -------------------------------------------------------------------------

    /** @test */
    public function save_priority_with_valid_priority_executes_without_error(): void {
        $_POST['draft_priority'] = 'high';

        $this->saveDraftPriority->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    /** @test */
    public function save_priority_with_invalid_priority_executes_without_error(): void {
        $_POST['draft_priority'] = 'invalid';

        $this->saveDraftPriority->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    /** @test */
    public function save_priority_with_none_executes_without_error(): void {
        $_POST['draft_priority'] = 'none';

        $this->saveDraftPriority->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }

    /** @test */
    public function save_priority_without_post_key_executes_without_error(): void {
        // $_POST['draft_priority'] is intentionally not set.

        $this->saveDraftPriority->invoke( $this->plugin, 1 );

        $this->assertTrue( true );
    }
}
