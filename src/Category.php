<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\TermPostsRelation;

class Category extends Term {
	/**
	 * Posts assigned to this category.
	 *
	 * @var Post[]
	 */
	#[TermPostsRelation( Post::class )]
	public array $posts = array();
}
