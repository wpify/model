<?php

namespace WpifyModel;

use WP_Post;

abstract class AbstractPostRepository extends AbstractRepository {
	/**
	 * @return string
	 */
	protected function post_type(): string {
		return 'post';
	}

	/**
	 * @param array $args
	 *
	 * @return array|mixed
	 */
	public function find( array $args = array() ) {
		$args['post_type'] = $this->post_type();

		return array_map( array( $this, 'factory' ), get_posts( $args ) );
	}

	/**
	 * @param null $object
	 */
	public function get( $object = null ) {
		try {
			return $this->factory( $object );
		} catch ( NotFoundException $exception ) {
			return null;
		}
	}

	/**
	 * @param ModelInterface $model
	 *
	 * @return array|false|WP_Post|null
	 */
	public function delete( ModelInterface $model ) {
		return wp_delete_post( $model->id, true );
	}
}
