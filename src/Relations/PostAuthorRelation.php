<?php

namespace WpifyModel\Relations;

use WpifyModel\Interfaces\PostModelInterface;
use WpifyModel\Interfaces\RelationInterface;
use WpifyModel\Interfaces\UserRepositoryInterface;

class PostAuthorRelation implements RelationInterface {
	/** @var PostModelInterface */
	private $model;

	/** @var UserRepositoryInterface */
	private $repository;

	public function __construct( PostModelInterface $model, UserRepositoryInterface $repository ) {
		$this->model      = $model;
		$this->repository = $repository;
	}

	public function fetch() {
		return isset( $this->model->author_id )
			? $this->repository->get( $this->model->author_id )
			: null;
	}

	public function assign() {
	}
}
