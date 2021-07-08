<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermRepository;

/**
 * Class Categories
 *
 * @package Test
 *
 * @method Category[] all( array $args = array() )
 */
class CategoryRepository extends AbstractTermRepository {
	static function model(): string {
		return Category::class;
	}

	static function taxonomy(): string {
		return 'category';
	}
}
