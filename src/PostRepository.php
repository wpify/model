<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractPostRepository;

/**
 * Class BasePostRepository
 * @package WpifyModel
 *
 * @method Post get( $object = null )
 * @method Post[] find( array $args = array() )
 * @method Post[] all()
 */
class PostRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'post';
	}

	static function model(): string {
		return Post::class;
	}
}
