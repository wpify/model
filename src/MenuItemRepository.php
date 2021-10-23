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
class MenuItemRepository extends AbstractPostRepository {
	static function post_type(): string {
		return 'nav_menu_item';
	}

	public function model(): string {
		return MenuItem::class;
	}
}
