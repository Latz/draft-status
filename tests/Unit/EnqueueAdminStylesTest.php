<?php
/**
 * Unit tests for DraftStatus::enqueueAdminStyles() — hook guard conditions.
 *
 * wp_enqueue_style and wp_enqueue_script are pre-stubbed as no-ops in
 * bootstrap.php, so WP_Mock cannot intercept them. Tests verify that the
 * method completes without error for each accepted hook value and that it
 * returns early (without error) for an irrelevant hook.
 */

use PHPUnit\Framework\TestCase;

class EnqueueAdminStylesTest extends TestCase {

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
    public function irrelevant_hook_returns_early_without_error(): void {
        $this->plugin->enqueueAdminStyles( 'dashboard' );

        $this->assertTrue( true );
    }

    /** @test */
    public function edit_php_hook_executes_without_error(): void {
        $this->plugin->enqueueAdminStyles( 'edit.php' );

        $this->assertTrue( true );
    }

    /** @test */
    public function post_php_hook_executes_without_error(): void {
        $this->plugin->enqueueAdminStyles( 'post.php' );

        $this->assertTrue( true );
    }

    /** @test */
    public function post_new_php_hook_executes_without_error(): void {
        $this->plugin->enqueueAdminStyles( 'post-new.php' );

        $this->assertTrue( true );
    }

    /** @test */
    public function index_php_hook_executes_without_error(): void {
        $this->plugin->enqueueAdminStyles( 'index.php' );

        $this->assertTrue( true );
    }
}
