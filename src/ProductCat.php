<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\TermPostsRelation;

class ProductCat extends Term {
	/**
	 * Products assigned to this tag.
	 *
	 * @var Post[]
	 */
	#[TermPostsRelation( Product::class )]
	public array $products = array();
}
