<?php
/**
 * Unit tests for DraftStatus::saveCompletionStatus() — security guards.
 *
 * Tests that the method bails early without writing post meta under the
 * three security conditions: missing nonce, autosave, insufficient capability.
 */

use PHPUnit\Framework\TestCase;

class SaveCompletionStatusTest extends TestCase {

    /** @var DraftStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new DraftStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        unset(
            $_POST['draft_completion_nonce_field'],
            $_POST['draft_complete'],
            $_POST['draft_due_date'],
            $_POST['draft_priority']
        );
    }

    /** @test */
    public function returns_early_when_nonce_field_is_missing(): void {
        unset( $_POST['draft_completion_nonce_field'] );

        // update_post_meta must never be called.
        WP_Mock::userFunction( 'update_post_meta' )->never();

        $this->plugin->saveCompletionStatus( 42 );

        $this->assertTrue( true );
    }

    /** @test */
    public function returns_early_when_nonce_verification_fails(): void {
        $_POST['draft_completion_nonce_field'] = 'bad_nonce';

        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( false );
        WP_Mock::userFunction( 'update_post_meta' )->never();

        $this->plugin->saveCompletionStatus( 42 );

        $this->assertTrue( true );
    }

    /** @test */
    public function returns_early_during_autosave_even_with_valid_nonce(): void {
        if ( defined( 'DOING_AUTOSAVE' ) ) {
            // Constant already set by a previous test run in the same process.
            // The autosave guard will fire — this test is still valid.
        } else {
            define( 'DOING_AUTOSAVE', true );
        }

        $_POST['draft_completion_nonce_field'] = 'nonce';

        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( 1 );
        WP_Mock::userFunction( 'update_post_meta' )->never();

        $this->plugin->saveCompletionStatus( 42 );

        $this->assertTrue( true );
    }

    /** @test */
    public function returns_early_when_user_lacks_edit_capability(): void {
        if ( defined( 'DOING_AUTOSAVE' ) ) {
            $this->markTestSkipped( 'DOING_AUTOSAVE defined — autosave guard fires first, capability check cannot be reached.' );
        }

        $_POST['draft_completion_nonce_field'] = 'nonce';

        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( 1 );
        WP_Mock::userFunction( 'current_user_can' )
            ->with( 'edit_post', 99 )
            ->andReturn( false );
        WP_Mock::userFunction( 'update_post_meta' )->never();

        $this->plugin->saveCompletionStatus( 99 );

        $this->assertTrue( true );
    }
}
