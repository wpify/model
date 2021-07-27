<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractPostRepository;

/**
 * Class BasePageRepository
 *
 * @package WpifyModel
 *
 * @method Page[] all()
 * @method Page[] find( array $args = array() )
 * @method Page create()
 * @method Page get( $object = null )
 * @method mixed save( $model )
 * @method mixed delete( $model )
 */
class PageRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'page';
	}

	public function model(): string {
		return Page::class;
	}
}
