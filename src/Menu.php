<?php

namespace Wpify\Model;

use Wpify\Model\Abstracts\AbstractTermModel;
use Wpify\Model\Interfaces\RepositoryInterface;

/**
 * Class BasicPost
 * @package Wpify\Model
 */
class Menu extends AbstractTermModel {
	public $items = [];

	public function __construct( $object, RepositoryInterface $repository ) {
		parent::__construct( $object, $repository );
	}

	public function items_relation(  ) {

	}

	/**
	 * @param array $menu
	 *
	 * @internal
	 */
	protected function strip_to_depth_limit( $menu, $current = 1 ) {
		$depth = (int) $this->depth; // Confirms still int.
		if ( $depth <= 0 ) {
			return $menu;
		}

		foreach ( $menu as &$currentItem ) {
			if ( $current == $depth ) {
				$currentItem->children = false;
				continue;
			}

			$currentItem->children = self::strip_to_depth_limit( $currentItem->children, $current + 1 );
		}

		return $menu;
	}
}
