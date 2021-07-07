<?php

namespace WpifyModel;

use WpifyModel\AbstractTermRepository;

/**
 * Class Categories
 *
 * @package Test
 *
 * @method BasicPostCategory[] all( array $args = array() )
 */
class BasicPostCategoryRepository extends AbstractTermRepository {
	static function model(): string {
		return BasicPostCategory::class;
	}
}
