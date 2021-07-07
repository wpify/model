<?php

namespace WpifyModel;

/**
 * Class BasePostRepository
 * @package WpifyModel
 * @method BasicPost get( $object = null )
 * @method BasicPost[] find( array $args = array() )
 */
final class BasicPostRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'post';
	}

	static function model(): string {
		return BasicPost::class;
	}
}
