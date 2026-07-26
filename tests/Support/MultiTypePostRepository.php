<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\PostRepository;

/**
 * Invalid repository: multiple post types, so create() must refuse.
 */
class MultiTypePostRepository extends PostRepository {
	public function post_types(): array {
		return array( 'post', 'page' );
	}
}
