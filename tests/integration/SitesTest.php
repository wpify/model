<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Site;
use Wpify\Model\SiteRepository;
use Wpify\Model\Tests\TestCase;

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
		$this->markTestSkipped( 'SiteRepository::save() uses comment and user functions — https://github.com/wpify/model/issues/28' );
	}

	/**
	 * @see https://github.com/wpify/model/issues/32
	 */
	public function test_admin_user_relation_resolves(): void {
		$this->markTestSkipped( 'Site::admin_user passes User::class as ManyToOneRelation source key — https://github.com/wpify/model/issues/32' );
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
