<?php

namespace WpifyModel;

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
}
