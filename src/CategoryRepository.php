<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermRepository;

/**
 * Class Categories
 *
 * @package Test
 *
 * @method Category[] all( array $args = array() )
 * @method Category[] children_of( ?int $parent_id )
 * @method Category[] not_empty()
 * @method Category[] find( array $args = array() )
 * @method Category create()
 * @method Category get( $object = null )
 * @method mixed save( $model )
 * @method mixed delete( $model )
 */
class CategoryRepository extends AbstractTermRepository {

	/** @var PostRepository */
	protected $post_repository;

	public function model(): string {
		return Category::class;
	}

	static function taxonomy(): string {
		return 'category';
	}

	/**
	 * @return PostRepository
	 */
	public function get_post_repository(): PostRepository {
		if ( empty( $this->post_repository ) ) {
			$this->post_repository = new PostRepository();
		}

		return $this->post_repository;
	}
}
