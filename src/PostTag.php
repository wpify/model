<?php

namespace Wpify\Model;

use Wpify\Model\Abstracts\AbstractTermModel;
use Wpify\Model\Interfaces\PostModelInterface;
use Wpify\Model\Relations\TermPostsRelation;

class PostTag extends AbstractTermModel {
	/** @var PostModelInterface */
	public $posts;

	protected function posts_relation(): TermPostsRelation {
		return new TermPostsRelation(
			$this,
			'posts',
			$this->model_repository()->get_post_repository()
		);
	}
}
