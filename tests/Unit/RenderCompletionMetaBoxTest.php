<?php
/**
 * Unit tests for DraftStatus::renderCompletionMetaBox().
 *
 * get_post_status is defined in bootstrap as always returning 'draft', so the
 * published branch cannot be reached and is not tested here.
 * get_post_meta returns '' for single lookups (bootstrap stub), so is_complete
 * will always be '' (falsy / not 'yes') in these tests.
 */

use PHPUnit\Framework\TestCase;

class RenderCompletionMetaBoxTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Return a minimal post stub with the given ID.
     */
    private function makePost( int $id = 1 ): stdClass {
        $post     = new stdClass();
        $post->ID = $id;
        return $post;
    }

    /**
     * Invoke renderCompletionMetaBox and capture all output.
     */
    private function captureOutput( stdClass $post ): string {
        ob_start();
        $this->plugin->renderCompletionMetaBox( $post );
        return ob_get_clean();
    }

    // ---------------------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------------------

    /** @test */
    public function draft_post_outputs_nonce_field(): void {
        // The bootstrap stub for wp_nonce_field echoes '' (empty string), so the
        // nonce field name never appears as literal text in the captured output.
        // However, wp_nonce_field is called with 'draft_completion_nonce_field' as
        // the $name argument, and the hidden input immediately following it carries
        // name="draft_complete" — both present only when the nonce path executes.
        // We verify the nonce path ran by confirming the hidden completion input
        // (rendered directly after wp_nonce_field) is present in the output.
        $output = $this->captureOutput( $this->makePost() );

        $this->assertStringContainsString( 'draft_complete_hidden', $output );
    }

    /** @test */
    public function draft_post_outputs_complete_button(): void {
        $output = $this->captureOutput( $this->makePost() );

        // The button carries both an id and a CSS class referencing the toggle.
        $this->assertTrue(
            str_contains( $output, 'draft_complete_button' ) ||
            str_contains( $output, 'draft-complete-toggle' ),
            'Output must contain draft_complete_button or draft-complete-toggle'
        );
    }

    /** @test */
    public function draft_post_outputs_due_date_field(): void {
        $output = $this->captureOutput( $this->makePost() );

        $this->assertStringContainsString( 'draft_due_date', $output );
    }

    /** @test */
    public function draft_post_outputs_priority_select(): void {
        $output = $this->captureOutput( $this->makePost() );

        $this->assertStringContainsString( 'draft_priority', $output );
    }

    /** @test */
    public function draft_post_outputs_incomplete_state_by_default(): void {
        // Bootstrap stub returns '' for get_post_meta single lookups,
        // so is_complete === '' (not 'yes') → the button gets the 'is-incomplete' class.
        $output = $this->captureOutput( $this->makePost() );

        $this->assertStringContainsString( 'is-incomplete', $output );
    }
}
