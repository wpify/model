<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\Attributes\PostTermRelation;
use Wpify\Model\Category;
use Wpify\Model\Post;

/**
 * Post subtype with a single-term relation (no shipped model uses PostTermRelation).
 */
class PostWithPrimaryCategory extends Post {
	#[PostTermRelation( Category::class )]
	public ?Category $primary_category = null;
}
