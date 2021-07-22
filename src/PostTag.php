<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermModel;
use WpifyModel\Interfaces\PostModelInterface;
use WpifyModel\Relations\TermPostsRelation;

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
