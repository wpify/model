<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Attachment;
use Wpify\Model\AttachmentRepository;
use Wpify\Model\Category;
use Wpify\Model\CategoryRepository;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\IncorrectRepositoryException;
use Wpify\Model\Page;
use Wpify\Model\PageRepository;
use Wpify\Model\Post;
use Wpify\Model\PostRepository;
use Wpify\Model\Tests\Support\EmptyTypesPostRepository;
use Wpify\Model\Tests\Support\MultiTypePostRepository;
use Wpify\Model\Tests\Support\PostWithPrimaryCategory;
use Wpify\Model\Tests\Support\PostWithPrimaryCategoryRepository;
use Wpify\Model\Tests\TestCase;

class PostsTest extends TestCase {
	private function posts(): PostRepository {
		return $this->repo( PostRepository::class );
	}

	public function test_model_returns_post_class(): void {
		$this->assertSame( Post::class, $this->posts()->model() );
	}

	public function test_get_by_id_wp_post_slug_url_and_model(): void {
		$post_id = self::factory()->post->create( array( 'post_name' => 'gettable', 'post_title' => 'Gettable' ) );

		$by_id = $this->posts()->get( $post_id );
		$this->assertInstanceOf( Post::class, $by_id );
		$this->assertSame( $post_id, $by_id->id );

		$by_object = $this->posts()->get( get_post( $post_id ) );
		$this->assertSame( $post_id, $by_object->id );

		$by_slug = $this->posts()->get( 'gettable' );
		$this->assertSame( $post_id, $by_slug->id );

		$by_url = $this->posts()->get( get_permalink( $post_id ) );
		$this->assertSame( $post_id, $by_url->id );

		$this->assertSame( $by_id, $this->posts()->get( $by_id ), 'model instances pass through' );

		$this->assertNull( $this->posts()->get( 999999 ) );
	}

	public function test_post_types_defaults_to_post(): void {
		$this->assertSame( array( 'post' ), $this->posts()->post_types() );
	}

	public function test_create_sets_post_type_and_data(): void {
		$post = $this->posts()->create( array( 'title' => 'Fresh' ) );

		$this->assertSame( 'post', $post->post_type );
		$this->assertSame( 'Fresh', $post->title );
		$this->assertSame( 0, $post->id );
	}

	public function test_create_throws_for_multiple_post_types(): void {
		$repository = new MultiTypePostRepository();
		$this->manager->register_repository( $repository );

		$this->expectException( CouldNotSaveModelException::class );

		$repository->create();
	}

	public function test_create_throws_for_no_post_types(): void {
		$repository = new EmptyTypesPostRepository();
		$this->manager->register_repository( $repository );

		$this->expectException( CouldNotSaveModelException::class );

		$repository->create();
	}

	public function test_save_inserts_new_post(): void {
		$post        = $this->posts()->create();
		$post->title = 'Inserted';

		$saved = $this->posts()->save( $post );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'Inserted', get_post( $saved->id )->post_title );
	}

	public function test_save_updates_existing_post(): void {
		$post = $this->posts()->get( self::factory()->post->create() );

		$post->title = 'Updated title';
		$this->posts()->save( $post );

		$this->assertSame( 'Updated title', get_post( $post->id )->post_title );
	}

	/**
	 * save() writes Meta props under the property name instead of meta_key,
	 * so the value never round-trips.
	 *
	 * @see https://github.com/wpify/model/issues/33
	 */
	public function test_save_persists_meta_props(): void {
		$post                = $this->posts()->create();
		$post->title         = 'Templated';
		$post->page_template = 'templates/full-width.php';

		$saved = $this->posts()->save( $post );

		$this->assertSame( 'templates/full-width.php', get_post_meta( $saved->id, '_wp_page_template', true ) );
	}

	public function test_save_throws_when_update_fails(): void {
		$post = $this->posts()->get( self::factory()->post->create() );

		$post['id'] = 999999;

		$this->expectException( CouldNotSaveModelException::class );

		$this->posts()->save( $post );
	}

	public function test_delete_force_removes_post(): void {
		$post = $this->posts()->get( self::factory()->post->create() );

		$this->assertTrue( $this->posts()->delete( $post ) );
		$this->assertNull( get_post( $post->id ) );
	}

	public function test_delete_without_force_trashes_post(): void {
		$post = $this->posts()->get( self::factory()->post->create() );

		$this->assertTrue( $this->posts()->delete( $post, false ) );
		$this->assertSame( 'trash', get_post( $post->id )->post_status );
	}

	public function test_find_and_pagination_helpers(): void {
		self::factory()->post->create_many( 5 );

		$page_one = $this->posts()->find( array( 'posts_per_page' => 2, 'paged' => 1 ) );
		$this->assertCount( 2, $page_one );

		$pagination = $this->posts()->get_pagination();
		$this->assertSame( 5, $pagination['found_posts'] );
		$this->assertSame( 1, $pagination['current_page'] );
		$this->assertSame( 3, $pagination['total_pages'] );
		$this->assertSame( 2, $pagination['per_page'] );

		$links = $this->posts()->get_paginate_links( array( 'type' => 'array' ) );
		$this->assertIsArray( $links );
		$this->assertNotEmpty( $links );
	}

	public function test_find_without_pagination(): void {
		self::factory()->post->create_many( 3 );

		$found = $this->posts()->find_without_pagination();

		$this->assertCount( 3, $found );
		$this->assertContainsOnlyInstancesOf( Post::class, $found );
	}

	public function test_find_paginated_returns_items_and_pagination(): void {
		self::factory()->post->create_many( 4 );

		$result = $this->posts()->find_paginated( array( 'posts_per_page' => 3 ) );

		$this->assertCount( 3, $result['items'] );
		$this->assertSame( 4, $result['pagination']['found_posts'] );
		$this->assertArrayHasKey( 'paginate_link', $result );
	}

	public function test_find_all_includes_drafts_and_find_published_does_not(): void {
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$this->assertCount( 2, $this->posts()->find_all() );
		$this->assertCount( 1, $this->posts()->find_published() );
	}

	public function test_find_all_by_term(): void {
		$term_id = self::factory()->category->create();
		$in_term = self::factory()->post->create();
		self::factory()->post->create();

		wp_set_post_terms( $in_term, array( $term_id ), 'category' );

		$category = $this->repo( CategoryRepository::class )->get( $term_id );
		$found    = $this->posts()->find_all_by_term( $category );

		$this->assertCount( 1, $found );
		$this->assertSame( $in_term, $found[0]->id );
	}

	public function test_find_all_by_term_throws_for_repository_without_taxonomy(): void {
		$post = $this->posts()->get( self::factory()->post->create() );

		$this->expectException( IncorrectRepositoryException::class );

		$this->posts()->find_all_by_term( $post );
	}

	public function test_find_child_posts_of_and_children_relation(): void {
		$parent_id = self::factory()->post->create();
		$child_id  = self::factory()->post->create( array( 'post_parent' => $parent_id ) );

		$parent = $this->posts()->get( $parent_id );

		$children = $this->posts()->find_child_posts_of( $parent );
		$this->assertCount( 1, $children );
		$this->assertSame( $child_id, $children[0]->id );

		$this->assertSame( $child_id, $parent->children[0]->id, 'ChildPostsRelation resolves through the repository' );
	}

	public function test_find_by_ids(): void {
		$ids = self::factory()->post->create_many( 3 );
		self::factory()->post->create();

		$found = $this->posts()->find_by_ids( array( $ids[0], $ids[2] ) );

		$this->assertCount( 2, $found );
	}

	public function test_assign_post_to_term_and_post_terms_relation_persist(): void {
		$post     = $this->posts()->get( self::factory()->post->create() );
		$term_id  = self::factory()->category->create( array( 'name' => 'Assigned' ) );
		$category = $this->repo( CategoryRepository::class )->get( $term_id );

		$this->posts()->assign_post_to_term( $post, array( $category ) );

		$this->assertTrue( has_category( $term_id, $post->id ) );

		// Persist through save(): PostTermsRelation::persist.
		$other_term = $this->repo( CategoryRepository::class )->get( self::factory()->category->create() );
		$post->categories = array( $other_term );
		$this->posts()->save( $post );

		$this->assertTrue( has_category( $other_term->id, $post->id ) );
	}

	public function test_post_terms_relation_get_reads_categories_and_tags(): void {
		$post_id = self::factory()->post->create();
		$cat_id  = self::factory()->category->create();
		$tag_id  = self::factory()->tag->create();

		wp_set_post_terms( $post_id, array( $cat_id ), 'category' );
		wp_set_post_terms( $post_id, array( $tag_id ), 'post_tag' );

		$post = $this->posts()->get( $post_id );

		$this->assertSame( $cat_id, $post->categories[0]->id );
		$this->assertSame( $tag_id, $post->tags[0]->id );
	}

	public function test_post_term_relation_get_and_persist(): void {
		$this->manager->register_repository( new PostWithPrimaryCategoryRepository() );
		$repository = $this->manager->get_model_repository( PostWithPrimaryCategory::class );

		$post     = $repository->get( self::factory()->post->create() );
		$category = $this->repo( CategoryRepository::class )->get( self::factory()->category->create() );

		$post->primary_category = $category;
		$repository->save( $post );

		$this->assertTrue( has_category( $category->id, $post->id ) );

		$fresh = $repository->get( $post->id );
		$this->assertInstanceOf( Category::class, $fresh->primary_category );
		$this->assertSame( $category->id, $fresh->primary_category->id );
	}

	public function test_top_level_post_parent_relation(): void {
		$grandparent = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$parent      = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $grandparent ) );
		$child       = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $parent ) );

		$page = $this->repo( PageRepository::class )->get( $child );

		$this->assertInstanceOf( Page::class, $page->top_parent );
		$this->assertSame( $grandparent, $page->top_parent->id );
	}

	/**
	 * Post::parent is wired to source key `parent_post_id`, which no Post prop
	 * defines, so the relation always resolves to null.
	 *
	 * @see https://github.com/wpify/model/issues/29
	 */
	public function test_parent_relation_resolves_parent_post(): void {
		$parent_id = self::factory()->post->create();
		$child_id  = self::factory()->post->create( array( 'post_parent' => $parent_id ) );

		$child = $this->posts()->get( $child_id );

		$this->assertInstanceOf( Post::class, $child->parent );
		$this->assertSame( $parent_id, $child->parent->id );
	}

	public function test_post_getters(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Some content for excerpt.',
				'post_excerpt' => '',
			)
		);
		$post    = $this->posts()->get( $post_id );

		$this->assertSame( get_permalink( $post_id ), $post->get_permalink() );
		$this->assertSame( 'Some content for excerpt.', trim( $post->get_rendered_excerpt() ) );

		$attachment_id = self::factory()->attachment->create_object(
			'image.jpg',
			$post_id,
			array( 'post_mime_type' => 'image/jpeg' )
		);

		$post->persist_featured_image_id( $attachment_id );
		$this->assertSame( $attachment_id, $post->get_featured_image_id() );
	}

	public function test_page_repository(): void {
		$repository = $this->repo( PageRepository::class );

		$this->assertSame( Page::class, $repository->model() );
		$this->assertSame( array( 'page' ), $repository->post_types() );

		$page = $repository->get( self::factory()->post->create( array( 'post_type' => 'page' ) ) );
		$this->assertInstanceOf( Page::class, $page );
	}

	public function test_attachment_repository_and_url(): void {
		$repository = $this->repo( AttachmentRepository::class );

		$this->assertSame( Attachment::class, $repository->model() );
		$this->assertSame( array( 'attachment' ), $repository->post_types() );

		$attachment_id = self::factory()->attachment->create_object( 'file.jpg', 0, array( 'post_mime_type' => 'image/jpeg' ) );
		$attachment    = $repository->get( $attachment_id );

		$this->assertInstanceOf( Attachment::class, $attachment );
		$this->assertSame( wp_get_attachment_url( $attachment_id ), $attachment->get_url() );
	}
}
