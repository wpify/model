<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Comment;
use Wpify\Model\CommentRepository;
use Wpify\Model\Tests\TestCase;

class CommentsTest extends TestCase {
	private function comments(): CommentRepository {
		return $this->repo( CommentRepository::class );
	}

	public function test_model_returns_comment_class(): void {
		$this->assertSame( Comment::class, $this->comments()->model() );
	}

	public function test_get_by_id_object_and_model(): void {
		$comment_id = (int) self::factory()->comment->create( array( 'comment_content' => 'Hello' ) );

		$by_id = $this->comments()->get( $comment_id );
		$this->assertInstanceOf( Comment::class, $by_id );
		$this->assertSame( $comment_id, $by_id->id );
		$this->assertSame( 'Hello', $by_id->content );

		$by_object = $this->comments()->get( get_comment( $comment_id ) );
		$this->assertSame( $comment_id, $by_object->id );

		$this->assertSame( $by_id, $this->comments()->get( $by_id ) );

		$this->assertNull( $this->comments()->get( 999999 ) );
	}

	public function test_save_inserts_comment_row(): void {
		$post_id = self::factory()->post->create();

		$comment               = $this->comments()->create();
		$comment->post_id      = $post_id;
		$comment->content      = 'Inserted comment';
		$comment->author_name  = 'Tester';
		$comment->author_email = 'tester@example.org';
		$comment->date         = current_time( 'mysql' );
		$comment->date_gmt     = current_time( 'mysql', true );
		$comment->approved     = true;

		$this->comments()->save( $comment );

		$rows = get_comments( array( 'post_id' => $post_id ) );
		$this->assertCount( 1, $rows );
		$this->assertSame( 'Inserted comment', $rows[0]->comment_content );

		// A comment saved with approved=false must persist comment_approved as '0'.
		$unapproved              = $this->comments()->create();
		$unapproved->post_id     = $post_id;
		$unapproved->content     = 'Pending comment';
		$unapproved->author_name = 'Tester';
		$unapproved->approved    = false;

		$saved = $this->comments()->save( $unapproved );

		$this->assertSame( '0', get_comment( $saved->id )->comment_approved );
	}

	/**
	 * After insert, save() refreshes the model with get_user_by(), so the
	 * model never learns the created comment's id.
	 *
	 * @see https://github.com/wpify/model/issues/30
	 */
	public function test_save_refreshes_model_with_created_comment(): void {
		$post_id = self::factory()->post->create();

		$comment              = $this->comments()->create();
		$comment->post_id     = $post_id;
		$comment->content     = 'Refreshed comment';
		$comment->author_name = 'Tester';
		$comment->approved    = true;

		$saved = $this->comments()->save( $comment );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'Refreshed comment', get_comment( $saved->id )->comment_content );
		$this->assertSame( 'Refreshed comment', $saved->content );
	}

	public function test_save_throws_when_update_fails(): void {
		$comment = $this->comments()->get( (int) self::factory()->comment->create() );

		$comment['id'] = 999999;

		$this->expectException( \Wpify\Model\Exceptions\CouldNotSaveModelException::class );

		$this->comments()->save( $comment );
	}

	public function test_save_updates_comment(): void {
		$comment_id = (int) self::factory()->comment->create( array( 'comment_content' => 'Before' ) );
		$comment    = $this->comments()->get( $comment_id );

		$comment->content = 'After';
		$this->comments()->save( $comment );

		$this->assertSame( 'After', get_comment( $comment_id )->comment_content );
	}

	public function test_delete_removes_comment(): void {
		$comment = $this->comments()->get( (int) self::factory()->comment->create() );

		$this->assertTrue( $this->comments()->delete( $comment ) );
		$this->assertNull( get_comment( $comment->id ) );
	}

	public function test_find_and_find_all(): void {
		$post_id = self::factory()->post->create();
		self::factory()->comment->create_many( 3, array( 'comment_post_ID' => $post_id ) );

		$this->assertCount( 3, $this->comments()->find( array( 'post_id' => $post_id ) ) );
		$this->assertCount( 3, $this->comments()->find_all() );
	}

	public function test_find_by_post_id(): void {
		$post_id  = self::factory()->post->create();
		$other_id = self::factory()->post->create();

		self::factory()->comment->create( array( 'comment_post_ID' => $post_id ) );
		self::factory()->comment->create( array( 'comment_post_ID' => $other_id ) );

		$found = $this->comments()->find_by_post_id( $post_id );

		$this->assertCount( 1, $found );
		$this->assertSame( $post_id, $found[0]->post_id );
	}

	public function test_find_by_ids(): void {
		$post_id = self::factory()->post->create();
		$ids     = array_map( 'intval', self::factory()->comment->create_many( 3, array( 'comment_post_ID' => $post_id ) ) );

		$found = $this->comments()->find_by_ids( array( $ids[0], $ids[2] ) );

		$this->assertCount( 2, $found );
	}
}
