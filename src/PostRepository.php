<?php

namespace Wpify\Model;

use Wpify\Model\Abstracts\AbstractPostRepository;

/**
 * Class BasePostRepository
 * @package Wpify\Model
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
