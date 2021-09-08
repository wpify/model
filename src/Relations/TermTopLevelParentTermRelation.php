<?php

namespace Wpify\Model\Relations;

use Wpify\Model\Interfaces\RelationInterface;
use Wpify\Model\Interfaces\TermModelInterface;
use Wpify\Model\Interfaces\TermRepositoryInterface;
class TermTopLevelParentTermRelation implements RelationInterface
{
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
    public function __construct(TermModelInterface $model, TermRepositoryInterface $repository)
    {
        $this->model = $model;
        $this->repository = $repository;
    }
    public function fetch()
    {
    	$top_parent = null;
    	if (isset($this->model->parent_id)) {
		    $ancestors = get_ancestors($this->model->id, $this->repository::taxonomy());
		    $top_parent = end($ancestors);
	    }

        return $top_parent ? $this->repository->get($top_parent) : null;
    }
    public function assign()
    {
    }
}
