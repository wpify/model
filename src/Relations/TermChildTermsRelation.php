<?php

namespace Wpify\Model\Relations;

use Wpify\Model\Interfaces\RelationInterface;
use Wpify\Model\Interfaces\TermModelInterface;
use Wpify\Model\Interfaces\TermRepositoryInterface;

class TermChildTermsRelation implements RelationInterface {
	/** @var TermModelInterface */
	private $model;

	/** @var TermRepositoryInterface */
	private $repository;

	/** @var array */
	private $args;

	/**
	 * TermRelation constructor.
	 *
	 * @param TermModelInterface $model
	 * @param TermRepositoryInterface $repository
	 * @param array $args
	 */
	public function __construct( TermModelInterface $model, TermRepositoryInterface $repository, array $args = array() ) {
		$this->model      = $model;
		$this->repository = $repository;
		$this->args       = $args;
	}

	/**
	 * @return TermModelInterface[]
	 */
	public function fetch() {
		return $this->repository->children_of( $this->model->id, $this->args );
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
