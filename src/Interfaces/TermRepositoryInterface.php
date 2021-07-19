<?php

namespace WpifyModel\Interfaces;

interface TermRepositoryInterface extends RepositoryInterface {
	public function child_of( ?int $parent_id );
	public function terms_of_post( int $post_id );
}
