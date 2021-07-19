<?php

namespace WpifyModel\Relations;

use WpifyModel\Interfaces\RelationInterface;
use WpifyModel\Interfaces\TermModelInterface;
use WpifyModel\Interfaces\TermRepositoryInterface;

class TermChildTermsRelation implements RelationInterface {
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

	/**
	 * @return TermModelInterface[]
	 */
	public function fetch() {
		return $this->repository->child_of( $this->model->id );
	}

	public function assign() {
		if ( isset( $this->model->children ) && is_array( $this->model->children ) ) {
			foreach ( $this->model->children as $child ) {
				$child->parent_id = $this->model->id;
				$this->repository->save( $child );
			}
		}
	}
}
