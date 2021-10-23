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
		foreach ( $menu_items as $menu_item ) {
			$p = $this->post_repository->get($menu_item);

		}

		return $this->post_repository->all_by_term( $this->model );
	}

	public function assign() {
		$this->post_repository->assign_post_to_term( $this->model->{$this->key}, [ $this->model ], true );
	}
}