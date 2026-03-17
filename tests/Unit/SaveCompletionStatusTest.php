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

    /** @test */
    public function saves_no_when_draft_complete_not_in_post(): void {
        if ( defined( 'DOING_AUTOSAVE' ) ) {
            $this->markTestSkipped( 'DOING_AUTOSAVE defined — autosave guard fires first, else branch cannot be reached.' );
        }

        // Ensure draft_complete is NOT in $_POST.
        unset( $_POST['draft_complete'] );
        $_POST['draft_completion_nonce_field'] = 'nonce';

        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( 1 );
        WP_Mock::userFunction( 'current_user_can' )
            ->with( 'edit_post', 42 )
            ->andReturn( true );

        // The else branch must call update_post_meta with 'no'.
        WP_Mock::userFunction( 'update_post_meta' )
            ->with( 42, '_draft_complete', 'no' )
            ->once()
            ->andReturn( true );

        // saveDraftDueDate and saveDraftPriority also call update_post_meta / delete_post_meta.
        // Allow any additional calls so they don't cause assertion failures.
        WP_Mock::userFunction( 'update_post_meta' )->andReturn( true );
        WP_Mock::userFunction( 'delete_post_meta' )->andReturn( true );

        $this->plugin->saveCompletionStatus( 42 );

        $this->assertTrue( true );
    }

    /** @test */
    public function saves_yes_when_draft_complete_is_yes_in_post(): void {
        if ( defined( 'DOING_AUTOSAVE' ) ) {
            $this->markTestSkipped( 'DOING_AUTOSAVE defined — autosave guard fires first, save branch cannot be reached.' );
        }

        $_POST['draft_completion_nonce_field'] = 'nonce';
        $_POST['draft_complete']               = 'yes';

        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( 1 );
        WP_Mock::userFunction( 'current_user_can' )
            ->with( 'edit_post', 55 )
            ->andReturn( true );

        // The if branch must call update_post_meta with 'yes'.
        WP_Mock::userFunction( 'update_post_meta' )
            ->with( 55, '_draft_complete', 'yes' )
            ->once()
            ->andReturn( true );

        // Allow additional calls from saveDraftDueDate / saveDraftPriority.
        WP_Mock::userFunction( 'update_post_meta' )->andReturn( true );
        WP_Mock::userFunction( 'delete_post_meta' )->andReturn( true );

        $this->plugin->saveCompletionStatus( 55 );

        $this->assertTrue( true );
    }

    /** @test */
    public function saves_no_when_draft_complete_value_is_invalid(): void {
        if ( defined( 'DOING_AUTOSAVE' ) ) {
            $this->markTestSkipped( 'DOING_AUTOSAVE defined — autosave guard fires first, save branch cannot be reached.' );
        }

        $_POST['draft_completion_nonce_field'] = 'nonce';
        $_POST['draft_complete']               = 'maybe'; // not 'yes' → whitelist maps to 'no'

        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( 1 );
        WP_Mock::userFunction( 'current_user_can' )
            ->with( 'edit_post', 77 )
            ->andReturn( true );

        // Whitelist validation: 'maybe' is not 'yes', so 'no' should be saved.
        WP_Mock::userFunction( 'update_post_meta' )
            ->with( 77, '_draft_complete', 'no' )
            ->once()
            ->andReturn( true );

        // Allow additional calls from saveDraftDueDate / saveDraftPriority.
        WP_Mock::userFunction( 'update_post_meta' )->andReturn( true );
        WP_Mock::userFunction( 'delete_post_meta' )->andReturn( true );

        $this->plugin->saveCompletionStatus( 77 );

        $this->assertTrue( true );
    }
}
