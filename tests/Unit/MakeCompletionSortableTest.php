<?php
/**
 * Unit tests for DraftStatus::makeCompletionSortable().
 */

use PHPUnit\Framework\TestCase;

class MakeCompletionSortableTest extends TestCase {

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
    public function adds_draft_completion_to_sortable_columns(): void {
        $result = $this->plugin->makeCompletionSortable( [] );
        $this->assertArrayHasKey( 'draft_completion', $result );
    }

    /** @test */
    public function sortable_key_maps_to_draft_completion_orderby(): void {
        $result = $this->plugin->makeCompletionSortable( [] );
        $this->assertSame( 'draft_completion', $result['draft_completion'] );
    }

    /** @test */
    public function preserves_existing_sortable_columns(): void {
        $existing = [ 'title' => 'title', 'date' => 'date' ];
        $result   = $this->plugin->makeCompletionSortable( $existing );

        $this->assertArrayHasKey( 'title', $result );
        $this->assertArrayHasKey( 'date', $result );
    }
}
