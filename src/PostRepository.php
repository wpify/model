<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractPostRepository;

/**
 * Class BasePostRepository
 * @package WpifyModel
 *
 * @method Post[] all()
 * @method Post[] find( array $args = array() )
 * @method Post create()
 * @method Post get( $object = null )
 * @method mixed save( $model )
 * @method mixed delete( $model )
 */
class PostRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'post';
	}

	public function model(): string {
		return Post::class;
	}
}
