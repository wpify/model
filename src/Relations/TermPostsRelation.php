<?php

namespace WpifyModel\Relations;

use WpifyModel\Interfaces\PostRepositoryInterface;
use WpifyModel\Interfaces\RelationInterface;
use WpifyModel\Interfaces\TermModelInterface;
use WpifyModel\Interfaces\TermRepositoryInterface;

class TermPostsRelation implements RelationInterface {
	/** @var TermModelInterface */
	private $model;

	/** @var string */
	private $key;

	/** @var TermRepositoryInterface */
	private $term_repository;

	/** @var PostRepositoryInterface */
	private $post_repository;

	/**
	 * TermPostsRelation constructor.
	 *
	 * @param TermModelInterface $model
	 * @param string $key
	 * @param TermRepositoryInterface $term_repository
	 * @param PostRepositoryInterface $post_repository
	 */
	public function __construct(
		TermModelInterface $model,
		string $key,
		TermRepositoryInterface $term_repository,
		PostRepositoryInterface $post_repository
	) {
		$this->model           = $model;
		$this->key             = $key;
		$this->term_repository = $term_repository;
		$this->post_repository = $post_repository;
	}

	public function fetch() {
		// TODO: Implement fetch() method.
	}

	public function assign() {
		// TODO: Implement assign() method.
	}
}
