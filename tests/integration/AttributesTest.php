<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use stdClass;
use Wpify\Model\Exceptions\ModelException;
use Wpify\Model\Exceptions\RepositoryMethodNotImplementedException;
use Wpify\Model\Exceptions\RepositoryNotFoundException;
use Wpify\Model\Post;
use Wpify\Model\PostRepository;
use Wpify\Model\Tests\Support\AccessorSource;
use Wpify\Model\Tests\Support\RelationProbeModel;
use Wpify\Model\Tests\Support\RelationProbeRepository;
use Wpify\Model\Tests\TestCase;
use Wpify\Model\User;
use Wpify\Model\UserRepository;

class AttributesTest extends TestCase {
	private function make_probe(): RelationProbeModel {
		$this->manager->register_repository( new RelationProbeRepository() );

		return new RelationProbeModel( $this->manager );
	}

	public function test_source_object_reads_key_from_source(): void {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Sourced' ) );
		$post    = $this->repo( PostRepository::class )->get( $post_id );

		$this->assertSame( 'Sourced', $post->title );
		$this->assertSame( $post_id, $post->id );
	}

	public function test_source_object_traverses_dotted_paths_and_returns_null_for_missing(): void {
		$probe = $this->make_probe();

		$source                = new stdClass();
		$source->nested        = new stdClass();
		$source->nested->value = 'deep';
		$probe->source( $source );

		$this->assertSame( 'deep', $probe->nested_value );

		$empty_probe = $this->make_probe();
		$empty_probe->source( new stdClass() );

		// Missing path resolves to null, so __get falls back to the default.
		$this->assertSame( '', $empty_probe->nested_value );
	}

	public function test_alias_of_returns_aliased_property(): void {
		$probe         = $this->make_probe();
		$probe->ref_id = 33;

		$this->assertSame( 33, $probe->ref_alias );
	}

	public function test_accessor_object_get_and_set_call_source_methods(): void {
		$probe  = $this->make_probe();
		$source = new AccessorSource();
		$probe->source( $source );

		$this->assertSame( 'initial', $probe->acc );

		$probe->acc = 'written';

		$this->assertSame( 'written', $source->stored );
	}

	public function test_meta_reads_and_writes_via_model_save(): void {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, '_wp_page_template', 'template-custom.php' );

		$post = $this->repo( PostRepository::class )->get( $post_id );

		$this->assertSame( 'template-custom.php', $post->page_template );
	}

	public function test_ids_relation_finds_models_by_ids(): void {
		$post_ids = self::factory()->post->create_many( 2 );

		$probe      = $this->make_probe();
		$probe->ids = $post_ids;

		$found = $probe->posts_by_ids;

		$this->assertCount( 2, $found );
		$this->assertContainsOnlyInstancesOf( Post::class, $found );
	}

	public function test_many_to_one_relation_resolves_typed_target(): void {
		$user_id = self::factory()->user->create( array( 'display_name' => 'Author' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$post = $this->repo( PostRepository::class )->get( $post_id );

		$this->assertInstanceOf( User::class, $post->author );
		$this->assertSame( $user_id, $post->author->id );
	}

	public function test_many_to_one_relation_throws_for_untyped_property(): void {
		$probe         = $this->make_probe();
		$probe->ref_id = 1;

		$this->expectException( RepositoryNotFoundException::class );

		$probe->ref;
	}

	public function test_child_posts_relation_throws_when_repository_lacks_method(): void {
		$probe = $this->make_probe();

		$this->expectException( RepositoryMethodNotImplementedException::class );

		$probe->child_posts;
	}

	public function test_child_terms_relation_throws_when_repository_lacks_method(): void {
		$probe = $this->make_probe();

		$this->expectException( RepositoryMethodNotImplementedException::class );

		$probe->child_terms;
	}

	public function test_model_exception_carries_optional_wp_error(): void {
		$plain = new ModelException( 'no wp_error' );
		$this->assertNull( $plain->get_wp_error() );

		$wp_error = new \WP_Error( 'code', 'message' );
		$with     = new ModelException( 'with wp_error', 0, $wp_error );
		$this->assertSame( $wp_error, $with->get_wp_error() );
	}
}
