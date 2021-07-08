<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractPostRepository;

/**
 * Class BasePageRepository
 *
 * @package WpifyModel
 *
 * @method Page get( $object = null )
 * @method Page[] all();
 * @method Page[] find( array $args = array() )
 */
class PageRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'page';
	}

	static function model(): string {
		return Page::class;
	}
}
