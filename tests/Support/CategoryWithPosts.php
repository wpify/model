<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\Attributes\TermPostsRelation;
use Wpify\Model\Category;
use Wpify\Model\Post;

/**
 * Category subtype exposing its posts (no shipped model uses TermPostsRelation).
 */
class CategoryWithPosts extends Category {
	/** @var Post[] */
	#[TermPostsRelation( Post::class )]
	public array $posts = array();
}
