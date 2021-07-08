<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermRepository;

/**
 * Class Categories
 *
 * @package Test
 *
 * @method Category[] all( array $args = array() )
 * @method Category[] child_of( ?int $parent_id )
 * @method Category[] not_empty()
 * @method Category[] find( array $args = array() )
 * @method Category create()
 * @method Category get( $object = null )
 * @method mixed save( $model )
 * @method mixed delete( $model )
 */
class CategoryRepository extends AbstractTermRepository {
	static function model(): string {
		return Category::class;
	}

	static function taxonomy(): string {
		return 'category';
	}
}
