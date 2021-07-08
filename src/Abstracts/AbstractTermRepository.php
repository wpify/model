<?php

namespace WpifyModel\Abstracts;

use WP_Term;
use WpifyModel\Exceptions\NotFoundException;

abstract class AbstractTermRepository extends AbstractRepository {
	/**
	 * AbstractTermRepository constructor.
	 *
	 * @param array $relations
	 */
	public function __construct( array $relations = array() ) {
		$default_relations = array(
			'parent'   => array( array( $this, 'get' ), 'parent_id' ),
			'children' => array( array( $this, 'child_of' ), 'id' ),
		);

		parent::__construct( array_merge( $default_relations, $relations ) );
	}

	/**
	 * @return AbstractTermModel[]
	 */
	public function all() {
		$args = array( 'hide_empty' => false );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() ) {
		$defaults   = array( 'taxonomy' => $this::taxonomy() );
		$args       = wp_parse_args( $args, $defaults );
		$collection = array();
		$terms      = get_terms( $args );

		foreach ( $terms as $term ) {
			$collection[] = $this->factory( $term );
		}

		return $this->collection_factory( $collection );
	}

	abstract static function taxonomy(): string;

	/**
	 * @return array
	 */
	public function not_empty() {
		$args = array( 'hide_empty' => true );

		return $this->find( $args );
	}

	/**
	 * @param ?int $parent_id
	 *
	 * @return array
	 */
	public function child_of( ?int $parent_id ) {
		if ( $parent_id > 0 ) {
			$args = array( 'child_of' => $parent_id );

			return $this->find( $args );
		}

		return null;
	}

	/**
	 * @param $object
	 *
	 * @return mixed
	 */
	public function get( $object = null ) {
		return $this->factory( $object );
	}

	/**
	 * @param AbstractTermModel $model
	 *
	 * @return mixed
	 */
	public function delete( $model ) {
		return wp_delete_term( $model->id, $this::taxonomy() );
	}

	/**
	 * @param AbstractTermModel $model
	 *
	 * @return mixed
	 * @throws NotFoundException
	 */
	public function save( $model ) {
		$args = array(
			'name'        => $model->name,
			'description' => $model->description,
			'parent'      => $model->parent_id,
			'slug'        => $model->slug,
			'term_group'  => $model->group,
		);

		if ( $model->id > 0 ) {
			$result = wp_update_term( $model->id, $model->taxonomy_name, $args );
		} else {
			$result = wp_insert_term( $model->name, $model->taxonomy_name, $args );
		}

		if ( ! is_wp_error( $result ) ) {
			// save the meta data
			foreach ( $model->get_props() as $key => $prop ) {
				if ( $prop['source'] === 'meta' ) {
					$model->set_meta( $prop['source_name'], $this->$key );
				}
			}

			$object = $this->resolve_object( $result['term_id'] );

			$model->refresh( $object );
		}

		return $result;
	}

	/**
	 * @param $data
	 *
	 * @return ?WP_Term
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data = null ): ?WP_Term {
		$object = null;

		if ( is_object( $data ) && get_class( $data ) === $this::model() ) {
			$object = $data->get_object();
		} elseif ( $data instanceof WP_Term ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WP_Term( (object) array(
				'taxonomy' => $this::taxonomy(),
			) );
		} elseif ( isset( $data->id ) ) {
			$object = get_term_by( 'ID', $data->id, $this::taxonomy() );
		} elseif ( is_string( $data ) ) {
			$object = get_term_by( 'slug', $data, $this::taxonomy() );
		} elseif ( is_int( $data ) ) {
			$object = get_term_by( 'ID', $data, $this::taxonomy() );
		} elseif ( is_array( $data ) && isset( $data['field'] ) && isset( $data['value'] ) ) {
			$object = get_term_by( $data['field'], $data['value'], $this::taxonomy() );
		}

		if ( ! is_object( $object ) ) {
			throw new NotFoundException( 'The term was not found' );
		}

		return $object;
	}

	abstract static function model(): string;
}
