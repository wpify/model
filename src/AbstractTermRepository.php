<?php

namespace WpifyModel;

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
	 * @return mixed
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
		$defaults   = array( 'taxonomy' => $this::model()::taxonomy() );
		$args       = wp_parse_args( $args, $defaults );
		$collection = array();
		$terms      = get_terms( $args );

		foreach ( $terms as $term ) {
			try {
				$collection[] = $this->factory( $term );
			} catch ( NotFoundException $e ) {
			}
		}

		return $this->collection_factory( $collection );
	}

	/**
	 * @return array
	 */
	public function not_empty() {
		$args = array( 'hide_empty' => true );

		return $this->find( $args );
	}

	/**
	 * @param int $parent_id
	 *
	 * @return array
	 */
	public function child_of( int $parent_id ) {
		$args = array( 'child_of' => $parent_id );

		return $this->find( $args );
	}

	/**
	 * @param $object
	 *
	 * @return mixed
	 */
	public function get( $object = null ) {
		try {
			return $this->factory( $object );
		} catch ( NotFoundException $e ) {
			return null;
		}
	}

	/**
	 * @param ModelInterface $model
	 *
	 * @return mixed
	 */
	public function delete( ModelInterface $model ) {
		return wp_delete_term( $model->id, $this->model()::taxonomy() );
	}
}
