<?php

namespace WpifyModel\Relations;

use WpifyModel\Interfaces\PostRepositoryInterface;
use WpifyModel\Interfaces\RelationInterface;
use WpifyModel\Interfaces\TermModelInterface;

class TermPostsRelation implements RelationInterface {
	/** @var TermModelInterface */
	private $model;

	/** @var string */
	private $key;

	/** @var PostRepositoryInterface */
	private $post_repository;

	/**
	 * TermPostsRelation constructor.
	 *
	 * @param TermModelInterface $model
	 * @param string $key
	 * @param PostRepositoryInterface $post_repository
	 */
	public function __construct(
		TermModelInterface $model,
		string $key,
		PostRepositoryInterface $post_repository
	) {
		$this->model           = $model;
		$this->key             = $key;
		$this->post_repository = $post_repository;
	}

	public function fetch() {
		return $this->post_repository->all_by_term( $this->model );
	}

	public function assign() {
		$this->post_repository->assign_post_to_term( $this->model->{$this->key}, [ $this->model ], true );
	}
}
