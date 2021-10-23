<?php

namespace Wpify\Model\Relations;

use Wpify\Model\Interfaces\PostRepositoryInterface;
use Wpify\Model\Interfaces\RelationInterface;
use Wpify\Model\Interfaces\TermModelInterface;

class MenuItemsRelation implements RelationInterface {
	/** @var TermModelInterface */
	private $model;

	/** @var string */
	private $key;

	/** @var PostRepositoryInterface */
	private $post_repository;

	/**
	 * TermPostsRelation constructor.
	 *
	 * @param TermModelInterface      $model
	 * @param string                  $key
	 * @param PostRepositoryInterface $post_repository
	 */
	public function __construct(
		TermModelInterface $model,
		PostRepositoryInterface $post_repository
	) {
		$this->model           = $model;
		$this->post_repository = $post_repository;
	}

	public function fetch() {
		$menu_items = wp_get_nav_menu_items( $this->model->id );

		$items = [];
		foreach ( $menu_items as $menu_item ) {
			$items[] = $this->post_repository->get( $menu_item );
		}

		return $this->sort_items( $items );
	}

	public function sort_items( $items ) {
		$result = [];
		foreach ( $items as $item ) {
			if ( ! $item->menu_item_parent || $item->menu_item_parent == 0 ) {
				$result[ $item->id ] = $item;
			} else {
				$children = $result[ $item->menu_item_parent ]->item_children ?: [];
				$children[] = $item;
				$result[ $item->menu_item_parent ]->item_children = $children;
			}
		}

		return $result;
	}


	public function assign() {
		// TODO
	}
}
