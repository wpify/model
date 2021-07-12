<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermRepository;

/**
 * Class Categories
 *
 * @package Test
 *
 * @method PostTag[] all( array $args = array() )
 * @method PostTag[] child_of( ?int $parent_id )
 * @method PostTag[] not_empty()
 * @method PostTag[] find( array $args = array() )
 * @method PostTag create()
 * @method PostTag get( $object = null )
 * @method mixed save( $model )
 * @method mixed delete( $model )
 */
class PostTagRepository extends AbstractTermRepository {
	static function model(): string {
		return PostTag::class;
	}

	static function taxonomy(): string {
		return 'post_tag';
	}
}