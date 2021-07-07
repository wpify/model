<?php

namespace WpifyModel;

use Doctrine\Common\Collections\ArrayCollection;
use WP_Query;

abstract class AbstractPostRepository extends AbstractRepository {
	/**
	 * AbstractPostRepository constructor.
	 *
	 * @param array $relations
	 */
	public function __construct( array $relations = array() ) {
		$default_relations = array(
			'parent'   => array( array( $this, 'get' ), 'parent_post' ),
		);

		parent::__construct( array_merge( $default_relations, $relations ) );
	}

	/**
	 * @return ArrayCollection
	 */
	public function all(): ArrayCollection {
		$args = array( 'posts_per_page' => - 1 );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() ) {
		$defaults   = array( 'post_type' => $this::post_type() );
		$args       = wp_parse_args( $args, $defaults );
		$query      = new WP_Query( $args );
		$collection = array();

		while ( $query->have_posts() ) {
			$query->the_post();

			global $post;

			try {
				$collection[] = $this->factory( $post );
			} catch ( NotFoundException $e ) {
			}
		}

		wp_reset_postdata();

		return $this->collection_factory( $collection );
	}

	/**
	 * @return string
	 */
	abstract static function post_type(): string;

	/**
	 * @param ?object $object
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
}
