<?php

namespace WpifyModel;

use Doctrine\Common\Collections\ArrayCollection;
use WP_Query;

abstract class AbstractPostRepository extends AbstractRepository {
	/**
	 * @param array $args
	 *
	 * @return ArrayCollection
	 */
	public function all( array $args = array() ): ArrayCollection {
		$defaults = array( 'posts_per_page' => - 1 );
		$args     = wp_parse_args( $args, $defaults );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return ArrayCollection
	 */
	public function find( array $args = array() ): ArrayCollection {
		$defaults   = array( 'post_type' => $this->post_type() );
		$args       = wp_parse_args( $args, $defaults );
		$query      = new WP_Query( $args );
		$collection = new ArrayCollection();

		while ( $query->have_posts() ) {
			$query->the_post();
			global $post;
			$collection->add( $this->factory( $post ) );
		}

		wp_reset_postdata();

		return $collection;
	}

	/**
	 * @return string
	 */
	protected function post_type(): string {
		return 'post';
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
	 * @return mixed
	 */
	public function delete( ModelInterface $model ) {
		return wp_delete_post( $model->id, true );
	}

	/**
	 * @param ModelInterface $model
	 *
	 * @return mixed
	 */
	public function save( ModelInterface $model ) {
		return $model->save();
	}
}
