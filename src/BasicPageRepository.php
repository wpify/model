<?php

namespace WpifyModel;

/**
 * Class BasePageRepository
 * @package WpifyModel
 * @method BasicPage get( $object = null )
 * @method BasicPage[] find( array $args = array() )
 */
final class BasicPageRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'page';
	}

	static function model(): string {
		return BasicPage::class;
	}
}
