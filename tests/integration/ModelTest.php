<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use ArrayIterator;
use ReflectionObject;
use Wpify\Model\Category;
use Wpify\Model\CategoryRepository;
use Wpify\Model\Exceptions\PropertyNotDefinedException;
use Wpify\Model\Exceptions\ReadOnlyPropertyException;
use Wpify\Model\Post;
use Wpify\Model\PostRepository;
use Wpify\Model\Tests\TestCase;

class ModelTest extends TestCase {
	private function make_post( array $args = array() ): Post {
		$post_id = self::factory()->post->create( array_merge( array( 'post_title' => 'Model post' ), $args ) );

		return $this->repo( PostRepository::class )->get( $post_id );
	}

	public function test_constructor_builds_props_from_typed_properties(): void {
		$post  = $this->repo( PostRepository::class )->create();
		$props = $post->props();

		$this->assertSame( 'id', $props['id']['name'] );
		$this->assertSame( 'int', $props['id']['type'] );
		$this->assertFalse( $props['id']['readonly'] );
		$this->assertTrue( $props['permalink']['readonly'] );
		$this->assertTrue( $props['author']['allows_null'] );
	}

	public function test_source_sets_and_gets_the_source(): void {
		$post = $this->make_post();

		$this->assertInstanceOf( \WP_Post::class, $post->source() );

		$other = get_post( self::factory()->post->create() );
		$post->source( $other );

		$this->assertSame( $other, $post->source() );
	}

	public function test_props_returns_metadata_for_all_public_properties(): void {
		$props = $this->make_post()->props();

		foreach ( array( 'id', 'title', 'content', 'slug', 'post_status', 'categories' ) as $key ) {
			$this->assertArrayHasKey( $key, $props );
		}
	}

	public function test_manager_returns_the_manager(): void {
		$this->assertSame( $this->manager, $this->make_post()->manager() );
	}

	public function test_reflection_returns_reflection_object(): void {
		$post = $this->make_post();

		$this->assertInstanceOf( ReflectionObject::class, $post->reflection() );
		$this->assertSame( Post::class, $post->reflection()->getName() );
	}

	public function test_refresh_drops_cached_values_and_sets_new_source(): void {
		$post = $this->make_post( array( 'post_title' => 'Before' ) );

		$this->assertSame( 'Before', $post->title );

		wp_update_post( array( 'ID' => $post->id, 'post_title' => 'After' ) );
		$post->refresh( get_post( $post->id ) );

		$this->assertSame( 'After', $post->title );
	}

	public function test_get_resolves_value_lazily_and_caches_it(): void {
		$post = $this->make_post( array( 'post_title' => 'Lazy' ) );

		$this->assertSame( 'Lazy', $post->title );

		wp_update_post( array( 'ID' => $post->id, 'post_title' => 'Changed behind the scenes' ) );

		// Still cached: __get must not re-resolve.
		$this->assertSame( 'Lazy', $post->title );
	}

	public function test_get_throws_for_undefined_property(): void {
		$this->expectException( PropertyNotDefinedException::class );

		$this->make_post()->does_not_exist;
	}

	public function test_set_stores_converted_value(): void {
		$post = $this->make_post();

		$post->title = 'New title';
		$this->assertSame( 'New title', $post->title );

		// Type conversion: int property receives a numeric string.
		$post->menu_order = '7';
		$this->assertSame( 7, $post->menu_order );
	}

	public function test_set_throws_for_readonly_property_with_resolved_value(): void {
		$post = $this->make_post();

		// Resolve first so the readonly guard sees a cached value.
		$this->assertNotEmpty( $post->permalink );

		$this->expectException( ReadOnlyPropertyException::class );

		$post->permalink = 'https://example.org/nope';
	}

	public function test_to_array_returns_selected_props(): void {
		$post = $this->make_post( array( 'post_title' => 'Array post' ) );
		$data = $post->to_array( array( 'id', 'title', 'slug' ) );

		$this->assertSame(
			array( 'id' => $post->id, 'title' => 'Array post', 'slug' => $post->slug ),
			$data
		);
	}

	public function test_to_array_recursive_converts_nested_models(): void {
		$post    = $this->make_post();
		$term_id = self::factory()->category->create( array( 'name' => 'Nested cat' ) );

		wp_set_post_terms( $post->id, array( $term_id ), 'category' );

		$data = $post->to_array( array( 'id', 'categories' ), array( 'categories' ) );

		$this->assertIsArray( $data['categories'] );
		$this->assertIsArray( $data['categories'][0] );
		$this->assertSame( 'Nested cat', $data['categories'][0]['name'] );
	}

	public function test_get_iterator_iterates_over_props(): void {
		$post     = $this->make_post();
		$iterator = $post->getIterator();

		$this->assertInstanceOf( ArrayIterator::class, $iterator );

		$data = iterator_to_array( $iterator );
		$this->assertSame( $post->id, $data['id'] );
	}

	public function test_offset_set_get_exists_unset(): void {
		$post = $this->make_post( array( 'post_title' => 'Offset post' ) );

		$this->assertTrue( isset( $post['title'] ) );
		$this->assertFalse( isset( $post['bogus'] ) );
		$this->assertSame( 'Offset post', $post['title'] );
		$this->assertNull( $post['bogus'] );

		$post['title'] = 'Via offset';
		$this->assertSame( 'Via offset', $post['title'] );

		unset( $post['title'] );
		$this->assertSame( 'Offset post', $post['title'], 'offsetUnset drops the cache so the value re-resolves from source' );
	}

	public function test_isset_and_unset_magic(): void {
		$post = $this->make_post( array( 'post_title' => 'Magic post' ) );

		$this->assertTrue( isset( $post->title ) );
		$this->assertFalse( isset( $post->bogus ) );

		$post->title = 'Cached';
		unset( $post->title );

		$this->assertSame( 'Magic post', $post->title, '__unset drops the cache so the value re-resolves from source' );
	}
}
