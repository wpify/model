<?php

namespace Wpify\Model\Relations;

use Wpify\Model\Interfaces\PostModelInterface;
use Wpify\Model\Interfaces\PostRepositoryInterface;
use Wpify\Model\Interfaces\RelationInterface;

class PostParentPostRelation implements RelationInterface {
	/** @var PostModelInterface */
	private $model;

	/** @var PostRepositoryInterface */
	private $repository;

	/**
	 * TermRelation constructor.
	 *
	 * @param PostModelInterface $model
	 * @param PostRepositoryInterface $repository
	 */
	public function __construct( PostModelInterface $model, PostRepositoryInterface $repository ) {
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
