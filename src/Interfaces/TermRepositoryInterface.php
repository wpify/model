<?php

namespace Wpify\Model\Interfaces;

interface TermRepositoryInterface extends RepositoryInterface {
	public function children_of( int $parent_id = 0, array $args = array() );

	public function terms_of_post( int $post_id );
}
