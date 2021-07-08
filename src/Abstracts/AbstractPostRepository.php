<?php

namespace WpifyModel\Abstracts;

use WP_Post;
use WP_Query;
use WpifyModel\Exceptions\NotFoundException;

abstract class AbstractPostRepository extends AbstractRepository {
	/**
	 * AbstractPostRepository constructor.
	 *
	 * @param array $relations
	 */
	public function __construct( array $relations = array() ) {
		$default_relations = array(
			'parent' => array(
				'fetch' => array( $this, 'fetch_parent' ),
			),
		);

		parent::__construct( array_merge( $default_relations, $relations ) );
	}

	public function fetch_parent( AbstractPostModel $model ) {
		return $this->get( $model->parent_id );
	}

	/**
	 * @param ?object $object
	 */
	public function get( $object = null ) {
		return ! empty( $object ) ? $this->factory( $object ) : null;
	}

	/**
	 * @return AbstractPostModel[]
	 */
	public function all() {
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

			$collection[] = $this->factory( $post );
		}

		wp_reset_postdata();

		return $this->collection_factory( $collection );
	}

	/**
	 * @return string
	 */
	abstract static function post_type(): string;

	/**
	 * @return AbstractPostModel
	 */
	public function create() {
		return $this->factory( null );
	}

	/**
	 * @param AbstractPostModel $model
	 *
	 * @return mixed
	 * @throws NotFoundException
	 */
	public function save( $model ) {
		$object_data = array();

		foreach ( $model->own_props() as $key => $prop ) {
			$source_name = $prop['source_name'];

			if ( $prop['source'] === 'object' ) {
				$object_data[ $source_name ] = $model->$key;
			} elseif ( $prop['source'] === 'meta' ) {
				if ( ! isset( $object_data['meta_input'] ) ) {
					$object_data['meta_input'] = array();
				}

				$object_data['meta_input'][ $source_name ] = $model->$key;
			} elseif ( $prop['source'] === 'relation' && is_callable( $prop['assign'] ) && $prop['changed'] ) {
				$prop['assign']( $model );
			}
		}

		if ( $model->id > 0 ) {
			$result = wp_update_post( $object_data, true );
		} else {
			$result = wp_insert_post( $object_data, true );
		}

		if ( ! is_wp_error( $result ) ) {
			$model->refresh( $this->resolve_object( $result ) );
		}

		return $result;
	}

	/**
	 * @param $data
	 *
	 * @return WP_Post
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data ): WP_Post {
		if ( is_object( $data ) && get_class( $data ) === $this::model() ) {
			$object = $data->source_object();
		} elseif ( $data instanceof WP_Post ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WP_Post( (object) array(
				'ID'            => null,
				'post_author'   => get_current_user_id(),
				'post_date'     => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', 1 ),
				'post_type'     => $this::post_type(),
			) );
		} elseif ( isset( $data->id ) ) {
			$object = get_post( $data->id );
		} else {
			$object = get_post( $data );
		}

		if ( ! ( $object instanceof WP_Post ) ) {
			throw new NotFoundException( 'The post was not found' );
		}

		return $object;
	}

	/**
	 * @param AbstractPostModel $model
	 *
	 * @return mixed
	 */
	public function delete( $model ) {
		return wp_delete_post( $model->id, true );
	}
}
