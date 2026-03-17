<?php
/**
 * Unit tests for DraftStatus::addCompletionFilterDropdown().
 */

use PHPUnit\Framework\TestCase;

class AddCompletionFilterDropdownTest extends TestCase {

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
    public function wrong_post_type_produces_no_output(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'page' );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    /** @test */
    public function post_type_outputs_completion_select(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft_completion_filter', $output );
    }

    /** @test */
    public function post_type_outputs_priority_select(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'draft_priority_filter', $output );
    }

    /** @test */
    public function outputs_complete_and_incomplete_options(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'complete', $output );
        $this->assertStringContainsString( 'incomplete', $output );
    }

    /** @test */
    public function outputs_priority_options(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'urgent', $output );
        $this->assertStringContainsString( 'high', $output );
    }
}
