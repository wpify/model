<?php

namespace WpifyModel\Relations;

use WpifyModel\Interfaces\RelationInterface;
use WpifyModel\Interfaces\TermModelInterface;
use WpifyModel\Interfaces\TermRepositoryInterface;

class TermParentTermRelation implements RelationInterface {
	/** @var TermModelInterface */
	private $model;

	/** @var TermRepositoryInterface */
	private $repository;

	/**
	 * TermRelation constructor.
	 *
	 * @param TermModelInterface $model
	 * @param TermRepositoryInterface $repository
	 */
	public function __construct( TermModelInterface $model, TermRepositoryInterface $repository ) {
		$this->model      = $model;
		$this->repository = $repository;
	}

	public function fetch() {
		return isset( $this->model->parent_id )
			? $this->repository->get( $this->model->parent_id )
			: null;
	}

	public function assign() {
	}
}
