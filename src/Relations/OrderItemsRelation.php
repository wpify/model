<?php

namespace WpifyModel\Relations;

use WpifyModel\Interfaces\ModelInterface;
use WpifyModel\Interfaces\PostModelInterface;
use WpifyModel\Interfaces\PostRepositoryInterface;
use WpifyModel\Interfaces\RelationInterface;
use WpifyModel\Interfaces\RepositoryInterface;
use WpifyModel\Order;
use WpifyModel\OrderItemRepository;

class OrderItemsRelation implements RelationInterface {
	/** @var Order */
	private $model;

	/** @var OrderItemRepository */
	private $repository;
	private string $type;

	/**
	 * TermRelation constructor.
	 *
	 * @param PostModelInterface      $model
	 * @param PostRepositoryInterface $repository
	 */
	public function __construct( ModelInterface $model, RepositoryInterface $repository, string $type = 'line_item' ) {
		$this->model      = $model;
		$this->repository = $repository;
		$this->type       = $type;
	}

	public function fetch() {
		$items = [];
		foreach ( $this->model->source_object()->get_items( $this->type ) as $item ) {
			$items[] = $this->repository->get( $item );
		}

		return $items;
	}

	public function assign() {
	}
}
