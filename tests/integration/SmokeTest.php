<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Manager;
use Wpify\Model\Post;
use Wpify\Model\PostRepository;
use Wpify\Model\Tests\Fixtures\WooFixtures;
use Wpify\Model\Tests\TestCase;

/**
 * Harness smoke tests: one per mode (core, WooCommerce, multisite).
 */
class SmokeTest extends TestCase {
	public function test_core_wordpress_is_loaded_and_library_autoloads(): void {
		$this->assertTrue( function_exists( 'do_action' ) );
		$this->assertInstanceOf( Manager::class, $this->manager );

		$post_id = self::factory()->post->create( array( 'post_title' => 'Smoke post' ) );
		$post    = $this->repo( PostRepository::class )->get( $post_id );

		$this->assertInstanceOf( Post::class, $post );
		$this->assertSame( 'Smoke post', $post->title );
	}

	public function test_woocommerce_is_installed(): void {
		$this->assertTrue( class_exists( 'WooCommerce' ) );
		$this->assertTrue( taxonomy_exists( 'product_cat' ) );

		$product = WooFixtures::simple_product();

		$this->assertGreaterThan( 0, $product->get_id() );
	}

	/**
	 * @group ms-required
	 */
	public function test_multisite_bootstrap(): void {
		$this->skipWithoutMultisite();

		$this->assertTrue( is_multisite() );
	}
}
