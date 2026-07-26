<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Tests\TestCase;
use Wpify\Model\User;
use Wpify\Model\UserRepository;

class UsersTest extends TestCase {
	private function users(): UserRepository {
		return $this->repo( UserRepository::class );
	}

	public function test_model_returns_user_class(): void {
		$this->assertSame( User::class, $this->users()->model() );
	}

	public function test_get_by_id_object_login_email_and_slug(): void {
		$user_id = self::factory()->user->create(
			array(
				'user_login'    => 'finduser',
				'user_email'    => 'find@example.org',
				'user_nicename' => 'find-nicename',
			)
		);

		$this->assertSame( $user_id, $this->users()->get( $user_id )->id );
		$this->assertSame( $user_id, $this->users()->get( get_user_by( 'id', $user_id ) )->id );
		$this->assertSame( $user_id, $this->users()->get( 'finduser' )->id );
		$this->assertSame( $user_id, $this->users()->get( 'find@example.org' )->id );
		$this->assertSame( $user_id, $this->users()->get( 'find-nicename' )->id );

		$model = $this->users()->get( $user_id );
		$this->assertSame( $model, $this->users()->get( $model ) );

		$this->assertNull( $this->users()->get( 999999 ) );
	}

	public function test_user_props_resolve_from_source_and_meta(): void {
		$user_id = self::factory()->user->create( array( 'user_email' => 'props@example.org' ) );
		update_user_meta( $user_id, 'first_name', 'Jane' );

		$user = $this->users()->get( $user_id );

		$this->assertSame( 'props@example.org', $user->email );
		$this->assertSame( 'Jane', $user->first_name );
		$this->assertContains( 'subscriber', $user->roles );
	}

	public function test_get_current_returns_logged_in_user_model(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$current = $this->users()->get_current();

		$this->assertInstanceOf( User::class, $current );
		$this->assertSame( $user_id, $current->id );
	}

	public function test_save_inserts_user(): void {
		$user        = $this->users()->create();
		$user->login = 'inserted_user';
		$user->email = 'inserted@example.org';

		// @-suppression: the model has no password prop, so wp_insert_user()
		// raises a user notice — https://github.com/wpify/model/issues/35
		$saved = @$this->users()->save( $user );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'inserted_user', get_user_by( 'id', $saved->id )->user_login );
	}

	public function test_save_updates_user_and_meta(): void {
		$user = $this->users()->get( self::factory()->user->create() );

		$user->first_name = 'Updated';
		$this->users()->save( $user );

		$this->assertSame( 'Updated', get_user_meta( $user->id, 'first_name', true ) );
	}

	public function test_save_throws_when_insert_fails(): void {
		$user = $this->users()->create();

		$this->expectException( CouldNotSaveModelException::class );

		// Empty login is rejected by wp_insert_user(); @ suppresses the
		// missing-password notice — https://github.com/wpify/model/issues/35
		@$this->users()->save( $user );
	}

	public function test_delete_removes_user(): void {
		$user = $this->users()->get( self::factory()->user->create() );

		$this->assertTrue( $this->users()->delete( $user ) );
		$this->assertFalse( get_user_by( 'id', $user->id ) );
	}

	public function test_find_and_find_all(): void {
		self::factory()->user->create_many( 2 );

		// The bootstrap admin exists as well.
		$found = $this->users()->find( array( 'role' => 'subscriber' ) );
		$this->assertCount( 2, $found );

		$this->assertGreaterThanOrEqual( 3, count( $this->users()->find_all() ) );
	}

	public function test_find_by_ids(): void {
		$ids = self::factory()->user->create_many( 3 );

		$found = $this->users()->find_by_ids( array( $ids[0], $ids[1] ) );

		$this->assertCount( 2, $found );
	}
}
