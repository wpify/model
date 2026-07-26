<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use WP_Site;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Site;
use Wpify\Model\SiteRepository;
use Wpify\Model\Tests\TestCase;
use Wpify\Model\User;

/**
 * @group ms-required
 */
class SitesTest extends TestCase {
	public function set_up(): void {
		parent::set_up();

		$this->skipWithoutMultisite();
	}

	private function sites(): SiteRepository {
		return $this->repo( SiteRepository::class );
	}

	public function test_model_returns_site_class(): void {
		$this->assertSame( Site::class, $this->sites()->model() );
	}

	public function test_get_by_id_object_and_model(): void {
		$blog_id = (int) self::factory()->blog->create();

		$by_id = $this->sites()->get( $blog_id );
		$this->assertInstanceOf( Site::class, $by_id );
		$this->assertSame( $blog_id, $by_id->id );
		$this->assertNotEmpty( $by_id->domain );

		$by_object = $this->sites()->get( get_site( $blog_id ) );
		$this->assertSame( $blog_id, $by_object->id );

		$this->assertSame( $by_id, $this->sites()->get( $by_id ) );

		$this->assertNull( $this->sites()->get( 999999 ) );
	}

	/**
	 * SiteRepository::save() calls wp_update_comment()/wp_insert_comment()
	 * and refreshes via get_user_by() — sites cannot be saved at all.
	 *
	 * @see https://github.com/wpify/model/issues/28
	 */
	public function test_save_persists_site(): void {
		$network_id = get_current_network_id();

		// Insert.
		$site = $this->sites()->create( array(
			'domain'  => 'example.org',
			'path'    => '/inserted-site/',
			'site_id' => $network_id,
		) );

		$saved = $this->sites()->save( $site );
		$this->assertGreaterThan( 0, $saved->id );

		$persisted = get_site( $saved->id );
		$this->assertInstanceOf( WP_Site::class, $persisted );
		$this->assertSame( '/inserted-site/', $persisted->path );

		// Update.
		$saved->path = '/updated-site/';
		$this->sites()->save( $saved );

		$this->assertSame( '/updated-site/', get_site( $saved->id )->path );
	}

	/**
	 * Saving a site whose domain+path collides with an existing one fails.
	 *
	 * @see https://github.com/wpify/model/issues/28
	 */
	public function test_save_throws_on_duplicate_site(): void {
		$duplicate = $this->sites()->create( array(
			'domain'  => 'example.org',
			'path'    => '/',
			'site_id' => get_current_network_id(),
		) );

		$this->expectException( CouldNotSaveModelException::class );

		$this->sites()->save( $duplicate );
	}

	/**
	 * @see https://github.com/wpify/model/issues/32
	 */
	public function test_admin_user_relation_resolves(): void {
		if ( ! is_site_meta_supported() ) {
			$this->markTestSkipped( 'site meta not supported' );
		}

		$blog_id = (int) self::factory()->blog->create();
		$user_id = (int) self::factory()->user->create();

		update_site_meta( $blog_id, 'admin_user_id', $user_id );

		$site = $this->sites()->get( $blog_id );

		$this->assertInstanceOf( User::class, $site->admin_user );
		$this->assertSame( $user_id, $site->admin_user->id );
	}

	public function test_delete_removes_site(): void {
		$site = $this->sites()->get( (int) self::factory()->blog->create() );

		$this->assertTrue( $this->sites()->delete( $site ) );
		$this->assertNull( get_site( $site->id ) );
	}

	public function test_find_find_all_and_find_by_ids(): void {
		$blog_ids = array(
			(int) self::factory()->blog->create(),
			(int) self::factory()->blog->create(),
		);

		$found = $this->sites()->find( array( 'site__in' => $blog_ids ) );
		$this->assertCount( 2, $found );

		// Main site + the two created ones.
		$this->assertGreaterThanOrEqual( 3, count( $this->sites()->find_all() ) );

		$by_ids = $this->sites()->find_by_ids( array( $blog_ids[0] ) );
		$this->assertCount( 1, $by_ids );
		$this->assertSame( $blog_ids[0], $by_ids[0]->id );
	}
}
