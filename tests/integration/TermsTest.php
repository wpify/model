<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Category;
use Wpify\Model\CategoryRepository;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\PostTag;
use Wpify\Model\PostTagRepository;
use Wpify\Model\Term;
use Wpify\Model\TermRepository;
use Wpify\Model\Tests\Support\CategoryWithPosts;
use Wpify\Model\Tests\Support\CategoryWithPostsRepository;
use Wpify\Model\Tests\TestCase;

class TermsTest extends TestCase {
	private function categories(): CategoryRepository {
		return $this->repo( CategoryRepository::class );
	}

	public function test_model_and_taxonomy_of_term_repositories(): void {
		$this->assertSame( Term::class, $this->repo( TermRepository::class )->model() );
		$this->assertSame( '', $this->repo( TermRepository::class )->taxonomy() );

		$this->assertSame( Category::class, $this->categories()->model() );
		$this->assertSame( 'category', $this->categories()->taxonomy() );

		$this->assertSame( PostTag::class, $this->repo( PostTagRepository::class )->model() );
		$this->assertSame( 'post_tag', $this->repo( PostTagRepository::class )->taxonomy() );
	}

	public function test_get_by_id_object_slug_name_and_model(): void {
		$term_id = self::factory()->category->create( array( 'name' => 'Fetch me', 'slug' => 'fetch-me' ) );

		$by_id = $this->categories()->get( $term_id );
		$this->assertInstanceOf( Category::class, $by_id );
		$this->assertSame( $term_id, $by_id->id );

		$by_object = $this->categories()->get( get_term( $term_id, 'category' ) );
		$this->assertSame( $term_id, $by_object->id );

		$by_slug = $this->categories()->get( 'fetch-me' );
		$this->assertSame( $term_id, $by_slug->id );

		$by_name = $this->categories()->get( 'Fetch me' );
		$this->assertSame( $term_id, $by_name->id );

		$this->assertSame( $by_id, $this->categories()->get( $by_id ) );

		$this->assertNull( $this->categories()->get( 999999 ) );
	}

	public function test_save_inserts_term(): void {
		$term = $this->categories()->create( array( 'name' => 'Inserted cat', 'taxonomy' => 'category' ) );

		$saved = $this->categories()->save( $term );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'Inserted cat', get_term( $saved->id, 'category' )->name );
	}

	/**
	 * The repository taxonomy() fallback never applies because Term::taxonomy
	 * defaults to '' instead of null.
	 *
	 * @see https://github.com/wpify/model/issues/31
	 */
	public function test_save_uses_repository_taxonomy_as_fallback(): void {
		$term = $this->categories()->create( array( 'name' => 'Fallback cat' ) );

		$saved = $this->categories()->save( $term );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'category', get_term( $saved->id, 'category' )->taxonomy );
	}

	public function test_save_updates_term(): void {
		$term = $this->categories()->get( self::factory()->category->create( array( 'name' => 'Before' ) ) );

		$term->name = 'After';
		$this->categories()->save( $term );

		$this->assertSame( 'After', get_term( $term->id, 'category' )->name );
	}

	public function test_save_throws_and_carries_wp_error_when_insert_fails(): void {
		$term = $this->categories()->create( array( 'taxonomy' => 'category' ) );

		try {
			$this->categories()->save( $term );
			$this->fail( 'Expected CouldNotSaveModelException' );
		} catch ( CouldNotSaveModelException $exception ) {
			$this->assertInstanceOf( \WP_Error::class, $exception->get_wp_error() );
		}
	}

	public function test_delete_removes_term(): void {
		$term = $this->categories()->get( self::factory()->category->create() );

		$this->assertTrue( $this->categories()->delete( $term ) );
		$this->assertNull( get_term( $term->id, 'category' ) );
	}

	public function test_find_honors_hide_empty_and_find_all_does_not(): void {
		$with_post = self::factory()->category->create();
		self::factory()->category->create();

		wp_set_post_terms( self::factory()->post->create(), array( $with_post ), 'category' );

		$not_empty = $this->categories()->find_not_empty();
		$this->assertCount( 1, $not_empty );
		$this->assertSame( $with_post, $not_empty[0]->id );

		// 'Uncategorized' exists by default, hence + 1.
		$this->assertCount( 3, $this->categories()->find_all() );
	}

	public function test_find_children_of(): void {
		$parent = self::factory()->category->create();
		$child  = self::factory()->category->create( array( 'parent' => $parent ) );

		$children = $this->categories()->find_children_of( $parent, array( 'hide_empty' => false ) );
		$this->assertCount( 1, $children );
		$this->assertSame( $child, $children[0]->id );

		$this->assertSame( array(), $this->categories()->find_children_of( 0 ) );
	}

	public function test_find_terms_of_post(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->category->create();

		wp_set_post_terms( $post_id, array( $term_id ), 'category' );

		$terms = $this->categories()->find_terms_of_post( $post_id );

		$this->assertCount( 1, $terms );
		$this->assertSame( $term_id, $terms[0]->id );

		// Posts always get the default category, so use tags for the empty case.
		$this->assertSame(
			array(),
			$this->repo( \Wpify\Model\PostTagRepository::class )->find_terms_of_post( self::factory()->post->create() )
		);
	}

	public function test_find_child_terms_of_and_children_relation(): void {
		$parent_id = self::factory()->category->create();
		$child_id  = self::factory()->category->create( array( 'parent' => $parent_id ) );

		$parent = $this->categories()->get( $parent_id );

		$children = $this->categories()->find_child_terms_of( $parent );
		$this->assertCount( 1, $children );
		$this->assertSame( $child_id, $children[0]->id );

		$this->assertSame( $child_id, $parent->children[0]->id, 'ChildTermsRelation resolves through the repository' );
	}

	public function test_find_by_ids(): void {
		$ids = array(
			self::factory()->category->create(),
			self::factory()->category->create(),
			self::factory()->category->create(),
		);

		$found = $this->categories()->find_by_ids( array( $ids[0], $ids[2] ), array( 'hide_empty' => false ) );

		$this->assertCount( 2, $found );
	}

	public function test_parent_and_top_parent_relations(): void {
		$grandparent = self::factory()->category->create();
		$parent      = self::factory()->category->create( array( 'parent' => $grandparent ) );
		$child_id    = self::factory()->category->create( array( 'parent' => $parent ) );

		$child = $this->categories()->get( $child_id );

		// The relations are typed ?Term, so they resolve through TermRepository.
		$this->assertInstanceOf( Term::class, $child->parent );
		$this->assertSame( $parent, $child->parent->id );

		$this->assertInstanceOf( Term::class, $child->top_parent );
		$this->assertSame( $grandparent, $child->top_parent->id );
	}

	public function test_term_permalink(): void {
		$term_id = self::factory()->category->create();
		$term    = $this->categories()->get( $term_id );

		$this->assertSame( get_term_link( $term_id, 'category' ), $term->get_permalink() );
		$this->assertSame( get_term_link( $term_id, 'category' ), $term->permalink );
	}

	public function test_term_posts_relation(): void {
		$this->manager->register_repository( new CategoryWithPostsRepository() );

		$term_id = self::factory()->category->create();
		$post_id = self::factory()->post->create();
		wp_set_post_terms( $post_id, array( $term_id ), 'category' );

		$category = $this->manager->get_model_repository( CategoryWithPosts::class )->get( $term_id );

		$this->assertCount( 1, $category->posts );
		$this->assertSame( $post_id, $category->posts[0]->id );
	}
}
