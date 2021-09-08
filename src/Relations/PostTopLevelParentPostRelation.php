<?php

namespace Wpify\Model\Relations;

use Wpify\Model\Interfaces\PostModelInterface;
use Wpify\Model\Interfaces\PostRepositoryInterface;
use Wpify\Model\Interfaces\RelationInterface;

class PostTopLevelParentPostRelation implements RelationInterface
{
	/** @var PostModelInterface */
	private $model;
	/** @var PostRepositoryInterface */
	private $repository;

	/**
	 * TermRelation constructor.
	 *
	 * @param  PostModelInterface  $model
	 * @param  PostRepositoryInterface  $repository
	 */
	public function __construct(PostModelInterface $model, PostRepositoryInterface $repository)
	{
		$this->model      = $model;
		$this->repository = $repository;
	}

	public function fetch()
	{
		$top_parent = null;
		if (isset($this->model->parent_id)) {
			$ancestors  = get_ancestors($this->model->id, $this->repository::post_type());
			$top_parent = end($ancestors);
		}

		return $top_parent ? $this->repository->get($top_parent) : null;
	}

	public function assign()
	{
	}
}
