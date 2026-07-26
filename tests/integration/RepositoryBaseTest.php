<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Post;
use Wpify\Model\PostRepository;
use Wpify\Model\Term;
use Wpify\Model\TermRepository;
use Wpify\Model\Tests\TestCase;

class RepositoryBaseTest extends TestCase {
	public function test_manager_throws_when_repository_is_not_registered(): void {
		$this->expectException( RepositoryNotInitialized::class );

		( new PostRepository() )->manager();
	}

	public function test_manager_returns_manager_after_registration(): void {
		$repository = new PostRepository();
		$this->manager->register_repository( $repository );

		$this->assertSame( $this->manager, $repository->manager() );
	}

	public function test_resolve_property_prefers_model_getter(): void {
		$post_id = self::factory()->post->create();
		$post    = $this->repo( PostRepository::class )->get( $post_id );

		$resolved = $this->repo( PostRepository::class )->resolve_property( $post->props()['permalink'], $post );

		$this->assertSame( get_permalink( $post_id ), $resolved );
	}

	public function test_resolve_property_falls_back_to_source_attribute(): void {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Resolved' ) );
		$post    = $this->repo( PostRepository::class )->get( $post_id );

		$resolved = $this->repo( PostRepository::class )->resolve_property( $post->props()['title'], $post );

		$this->assertSame( 'Resolved', $resolved );
	}

	private function prop( string $type, bool $allows_null = false, mixed $default = null ): array {
		return array(
			'name'        => 'probe',
			'type'        => $type,
			'readonly'    => false,
			'default'     => $default,
			'allows_null' => $allows_null,
		);
	}

	public function test_maybe_convert_to_type_scalars(): void {
		$repository = $this->repo( PostRepository::class );

		$this->assertSame( 42, $repository->maybe_convert_to_type( $this->prop( 'int' ), '42' ) );
		$this->assertSame( 1.5, $repository->maybe_convert_to_type( $this->prop( 'float' ), '1.5' ) );
		$this->assertSame( '42', $repository->maybe_convert_to_type( $this->prop( 'string' ), 42 ) );
		$this->assertTrue( $repository->maybe_convert_to_type( $this->prop( 'bool' ), 'true' ) );
		$this->assertFalse( $repository->maybe_convert_to_type( $this->prop( 'bool' ), 'false' ) );
		$this->assertTrue( $repository->maybe_convert_to_type( $this->prop( 'bool' ), 1 ) );
	}

	public function test_maybe_convert_to_type_array_and_object(): void {
		$repository = $this->repo( PostRepository::class );

		$this->assertSame( array( 'a' => 1 ), $repository->maybe_convert_to_type( $this->prop( 'array' ), '{"a":1}' ) );
		$this->assertSame( array( 5 ), $repository->maybe_convert_to_type( $this->prop( 'array' ), 5 ) );
		$this->assertNull( $repository->maybe_convert_to_type( $this->prop( 'array' ), 'not json' ), 'non-JSON strings decode to null' );

		$object = $repository->maybe_convert_to_type( $this->prop( 'object' ), '{"a":1}' );
		$this->assertSame( 1, $object->a );

		$cast = $repository->maybe_convert_to_type( $this->prop( 'object' ), array( 'b' => 2 ) );
		$this->assertSame( 2, $cast->b );
	}

	public function test_maybe_convert_to_type_null_with_allows_null_returns_default(): void {
		$repository = $this->repo( PostRepository::class );

		$this->assertSame( 'fallback', $repository->maybe_convert_to_type( $this->prop( 'string', true, 'fallback' ), null ) );
		$this->assertSame( 'fallback', $repository->maybe_convert_to_type( $this->prop( 'string', true, 'fallback' ), '' ) );
	}

	public function test_maybe_convert_to_type_resolves_model_classes_through_repositories(): void {
		$post_id = self::factory()->post->create();

		$converted = $this->repo( PostRepository::class )->maybe_convert_to_type( $this->prop( Post::class ), $post_id );

		$this->assertInstanceOf( Post::class, $converted );
		$this->assertSame( $post_id, $converted->id );
	}

	public function test_maybe_convert_to_type_leaves_unknown_classes_untouched(): void {
		$value = $this->repo( PostRepository::class )->maybe_convert_to_type( $this->prop( \stdClass::class ), 'raw' );

		$this->assertSame( 'raw', $value );
	}

	public function test_base_create_builds_model_and_assigns_data(): void {
		$term = $this->repo( TermRepository::class )->create( array( 'name' => 'Created term' ) );

		$this->assertInstanceOf( Term::class, $term );
		$this->assertSame( 'Created term', $term->name );
		$this->assertSame( 0, $term->id );
	}
}
