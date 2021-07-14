<?php

namespace WpifyModel\Abstracts;

use stdClass;
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
			'parent'   => array(
				'fetch' => array( $this, 'fetch_parent' ),
			),
			'children' => array(
				'fetch'  => array( $this, 'fetch_children' ),
				'assign' => array( $this, 'assign_children' ),
			),
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
	 * @return AbstractTermModel[]
	 */
	public function not_empty() {
		$args = array( 'hide_empty' => true );

		return $this->find( $args );
	}

	/**
	 * @return AbstractTermModel
	 */
	public function create() {
		return $this->factory( null );
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
	 * @return AbstractTermModel|null
	 */
	public function fetch_parent( $model ) {
		return $this->get( $model->parent_id );
	}

	/**
	 * @param $object
	 *
	 * @return ?AbstractTermModel
	 */
	public function get( $object = null ) {
		return ! empty( $object ) ? $this->factory( $object ) : null;
	}

	/**
	 * @param AbstractTermModel $model
	 *
	 * @return array
	 */
	public function fetch_children( $model ) {
		return $this->child_of( $model->id );
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

		return $this->collection_factory( array() );
	}

	/**
	 * @param AbstractTermModel $model
	 *
	 * @throws NotFoundException
	 */
	public function assign_children( $model ) {
		foreach ( $model->children as $child ) {
			$child->parent_id = $model->id;
			$this->save( $child );
		}
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

			// Term exists
			if ( is_wp_error( $result ) && is_int( $result->get_error_data() ) ) {
				$model->id = $result->get_error_data();
			} else {
				$model->id = $result['term_id'];
			}
		}

		if ( $model->id ) {
			// save the meta data
			foreach ( $model->own_props() as $key => $prop ) {
				if ( $prop['source'] === 'meta' && $prop['changed'] ) {
					$model->store_meta( $prop['source_name'], $model->$key );
				} elseif ( $prop['source'] === 'relation' && is_callable( $prop['assign'] ) && $prop['changed'] ) {
					$prop['assign']( $model );
				}
			}

			$object = $this->resolve_object( $model->id );

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
			$object = $data->source_object();
		} elseif ( $data instanceof WP_Term ) {
			$object = $data;
		} elseif ( empty( $data ) ) {
			$object           = new WP_Term( new stdClass() );
			$object->taxonomy = $this::taxonomy();
		} elseif ( isset( $data->id ) ) {
			$object = get_term_by( 'ID', $data->id, $this::taxonomy() );
		} elseif ( is_numeric( $data ) ) {
			$object = get_term_by( 'ID', (int) $data, $this::taxonomy() );
		} elseif ( is_string( $data ) ) {
			$object = get_term_by( 'slug', $data, $this::taxonomy() );
		} elseif ( is_int( $data ) ) {
			$object = get_term_by( 'ID', $data, $this::taxonomy() );
		} elseif ( is_array( $data ) && isset( $data['field'] ) && isset( $data['value'] ) ) {
			$object = get_term_by( $data['field'], $data['value'], $this::taxonomy() );
		}

		if ( ! is_object( $object ) ) {
			throw new NotFoundException( 'The term was not found', $data );
		}

		return $object;
	}

	abstract static function model(): string;
}
