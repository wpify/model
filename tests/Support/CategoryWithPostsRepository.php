<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\CategoryRepository;

class CategoryWithPostsRepository extends CategoryRepository {
	public function model(): string {
		return CategoryWithPosts::class;
	}
}
