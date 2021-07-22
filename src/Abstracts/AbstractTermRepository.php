<?php

namespace WpifyModel\Abstracts;

use stdClass;
use WP_Term;
use WpifyModel\Exceptions\NotFoundException;
use WpifyModel\Exceptions\NotPersistedException;
use WpifyModel\Interfaces\TermModelInterface;
use WpifyModel\Interfaces\TermRepositoryInterface;

abstract class AbstractTermRepository extends AbstractRepository implements TermRepositoryInterface {
	/**
	 * @param array $args
	 *
	 * @return AbstractTermModel[]
	 */
	public function all( array $args = array() ) {
		$args = array_merge( array( 'hide_empty' => false ), $args );

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
	 * @param int $parent_id
	 * @param array $args
	 *
	 * @return array
	 */
	public function children_of( int $parent_id = 0, array $args = array() ) {
		if ( $parent_id > 0 ) {
			$args = array_merge( array( 'child_of' => $parent_id ), $args );

			return $this->find( $args );
		}

		return $this->collection_factory( array() );
	}

	/**
	 * Retrieves the terms of the taxonomy that are attached to the post.
	 *
	 * @param int $post_id
	 *
	 * @return array|mixed
	 */
	public function terms_of_post( int $post_id ) {
		$collection = array();
		$terms      = get_the_terms( $post_id, $this::taxonomy() );

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$collection[] = $this->factory( $term );
			}
		}

		return $this->collection_factory( $collection );
	}

	public function posts_in_term( TermModelInterface $term ) {
		$collection = array();

		return $this->collection_factory( $collection );
	}

	/**
	 * @param AbstractTermModel $model
	 *
	 * @return mixed
	 * @throws NotFoundException
	 * @throws NotPersistedException
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

			if ( is_wp_error( $result ) ) {
				throw new NotPersistedException();
			}
		} else {
			$result = wp_insert_term( $model->name, $model->taxonomy_name, $args );

			// Term exists
			if ( is_wp_error( $result ) && is_int( $result->get_error_data() ) ) {
				$model->id = $result->get_error_data();
			} elseif ( is_wp_error( $result ) ) {
				throw new NotPersistedException();
			} else {
				$model->id = $result['term_id'];
			}
		}

		if ( $model->id ) {
			foreach ( $model->own_props() as $key => $prop ) {
				if ( $prop['source'] === 'meta' && $prop['changed'] ) {
					$model->store_meta( $prop['source_name'], $model->$key );
				} elseif ( $prop['source'] === 'relation' && isset( $prop['relation'] ) && method_exists( $prop['relation'], 'assign' ) && $prop['changed'] ) {
					$prop['relation']->assign();
				}
			}

			$object = $this->resolve_object( $model->id );

			$model->refresh( $object );
		}

		return $model;
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
			throw new NotFoundException( "The term (" . $this::taxonomy() . ") was not found\n\n" . print_r( $data, true ) );
		}

		return $object;
	}

	abstract static function model(): string;
}
