<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\PostRepository;

/**
 * Invalid repository: no post types, so create() must refuse.
 */
class EmptyTypesPostRepository extends PostRepository {
	public function post_types(): array {
		return array();
	}
}
