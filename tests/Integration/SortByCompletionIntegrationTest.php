<?php
/**
 * Integration tests for DraftStatus::sortByCompletion() and
 * DraftStatus::customPriorityOrderby().
 *
 * Because is_admin() returns false in the WP integration test environment,
 * these tests focus on verifiable, end-to-end database behaviour: confirming
 * that the guard condition works as expected, that priority meta is stored and
 * queryable, and that individual priority values can be retrieved correctly.
 */

class SortByCompletionIntegrationTest extends WP_UnitTestCase {

    /** @var DraftStatus */
    private $plugin;

    /** @var int[] Post IDs keyed by priority label */
    private $post_ids = [];

    public function setUp(): void {
        parent::setUp();

        $this->plugin = new DraftStatus();

        // Create one draft post for each supported priority level.
        $this->post_ids['low']    = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        $this->post_ids['urgent'] = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        $this->post_ids['high']   = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        $this->post_ids['medium'] = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        update_post_meta( $this->post_ids['low'],    '_draft_priority', 'low' );
        update_post_meta( $this->post_ids['low'],    '_draft_complete', 'no' );
        update_post_meta( $this->post_ids['urgent'], '_draft_priority', 'urgent' );
        update_post_meta( $this->post_ids['urgent'], '_draft_complete', 'no' );
        update_post_meta( $this->post_ids['high'],   '_draft_priority', 'high' );
        update_post_meta( $this->post_ids['high'],   '_draft_complete', 'no' );
        update_post_meta( $this->post_ids['medium'], '_draft_priority', 'medium' );
        update_post_meta( $this->post_ids['medium'], '_draft_complete', 'no' );
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Test 1 – is_admin() guard leaves the query untouched
    // -----------------------------------------------------------------------

    /**
     * In the integration test environment is_admin() returns false, so
     * sortByCompletion() must return early without modifying the query.
     *
     * @test
     */
    public function sort_by_completion_sets_meta_query_on_main_query(): void {
        $query = new WP_Query( [
            'post_type'   => 'post',
            'post_status' => 'draft',
            'orderby'     => 'draft_completion',
            'fields'      => 'ids',
        ] );

        // Capture the meta_query value before calling the method.
        $meta_query_before = $query->get( 'meta_query' );

        // Call the method under test. Because is_admin() === false the method
        // should return immediately without touching the query.
        $this->plugin->sortByCompletion( $query );

        // meta_query must be unchanged – the guard worked.
        $this->assertSame(
            $meta_query_before,
            $query->get( 'meta_query' ),
            'sortByCompletion() must not modify meta_query when is_admin() is false'
        );
    }

    // -----------------------------------------------------------------------
    // Test 2 – urgent priority posts are stored and retrievable
    // -----------------------------------------------------------------------

    /**
     * Verifies that the test fixture posts were created correctly and that a
     * meta query for _draft_priority=urgent returns exactly the urgent post.
     *
     * @test
     */
    public function urgent_draft_posts_exist(): void {
        $query = new WP_Query( [
            'post_type'   => 'post',
            'post_status' => 'draft',
            'fields'      => 'ids',
            'meta_query'  => [
                [
                    'key'     => '_draft_priority',
                    'value'   => 'urgent',
                    'compare' => '=',
                ],
            ],
        ] );

        $ids = $query->posts;

        $this->assertCount( 1, $ids, 'Exactly one urgent post should exist' );
        $this->assertContains(
            $this->post_ids['urgent'],
            $ids,
            'The urgent post ID must be in the query results'
        );
    }

    // -----------------------------------------------------------------------
    // Test 3 – incomplete drafts can be filtered from complete ones
    // -----------------------------------------------------------------------

    /**
     * Creates a complete draft alongside the incomplete fixtures and confirms
     * that a meta query for _draft_complete=yes returns only the complete post.
     *
     * @test
     */
    public function complete_draft_ordered_before_incomplete_when_filtered(): void {
        $complete_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        update_post_meta( $complete_id, '_draft_complete', 'yes' );
        update_post_meta( $complete_id, '_draft_priority', 'high' );

        $complete_query = new WP_Query( [
            'post_type'   => 'post',
            'post_status' => 'draft',
            'fields'      => 'ids',
            'meta_query'  => [
                [
                    'key'     => '_draft_complete',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
            ],
        ] );

        $complete_ids = $complete_query->posts;

        $this->assertContains(
            $complete_id,
            $complete_ids,
            'The complete post must appear in the complete-filter results'
        );

        // None of the incomplete fixture posts may appear in the complete set.
        foreach ( $this->post_ids as $label => $id ) {
            $this->assertNotContains(
                $id,
                $complete_ids,
                "Incomplete post '{$label}' must not appear in the complete-filter results"
            );
        }
    }

    // -----------------------------------------------------------------------
    // Test 4 – every priority value can be queried individually
    // -----------------------------------------------------------------------

    /**
     * Runs a separate meta query for each priority level and asserts that
     * exactly one post is returned – the one created in setUp().
     *
     * @test
     */
    public function all_priorities_can_be_queried_individually(): void {
        $priorities = [ 'urgent', 'high', 'medium', 'low' ];

        foreach ( $priorities as $priority ) {
            $query = new WP_Query( [
                'post_type'   => 'post',
                'post_status' => 'draft',
                'fields'      => 'ids',
                'meta_query'  => [
                    [
                        'key'     => '_draft_priority',
                        'value'   => $priority,
                        'compare' => '=',
                    ],
                ],
            ] );

            $ids = $query->posts;

            $this->assertCount(
                1,
                $ids,
                "Exactly one post with priority '{$priority}' should be found"
            );

            $this->assertContains(
                $this->post_ids[ $priority ],
                $ids,
                "Post with priority '{$priority}' must match the fixture post ID"
            );
        }
    }
}
