<?php
/**
 * Unit tests for DraftStatus meta box and dashboard widget registration methods.
 */

use PHPUnit\Framework\TestCase;

class AddCompletionMetaBoxTest extends TestCase {

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
    public function add_completion_meta_box_executes_without_error(): void {
        $this->plugin->addCompletionMetaBox();
        $this->assertTrue( true );
    }

    /** @test */
    public function add_dashboard_widget_executes_without_error(): void {
        $this->plugin->addDashboardWidget();
        $this->assertTrue( true );
    }

    /** @test */
    public function add_completion_column_adds_draft_completion_key(): void {
        $result = $this->plugin->addCompletionColumn( [ 'title' => 'Title' ] );
        $this->assertArrayHasKey( 'draft_completion', $result );
    }

    /** @test */
    public function add_completion_column_preserves_existing_columns(): void {
        $result = $this->plugin->addCompletionColumn( [ 'title' => 'Title', 'date' => 'Date' ] );
        $this->assertArrayHasKey( 'title', $result );
    }

    /** @test */
    public function make_completion_sortable_adds_draft_completion(): void {
        $result = $this->plugin->makeCompletionSortable( [] );
        $this->assertArrayHasKey( 'draft_completion', $result );
        $this->assertSame( 'draft_completion', $result['draft_completion'] );
    }

    /** @test */
    public function make_completion_sortable_preserves_existing(): void {
        $result = $this->plugin->makeCompletionSortable( [ 'title' => 'Title' ] );
        $this->assertArrayHasKey( 'title', $result );
    }
}
