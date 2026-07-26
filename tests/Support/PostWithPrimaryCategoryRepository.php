<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\PostRepository;

class PostWithPrimaryCategoryRepository extends PostRepository {
	public function model(): string {
		return PostWithPrimaryCategory::class;
	}
}
