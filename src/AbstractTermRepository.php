<?php

namespace WpifyModel;

use Doctrine\Common\Collections\ArrayCollection;

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
	 * @return ArrayCollection
	 */
	public function all(): ArrayCollection {
		$args = array( 'hide_empty' => false );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return ArrayCollection
	 */
	public function find( array $args = array() ): ArrayCollection {
		$defaults   = array( 'taxonomy' => $this::model()::taxonomy() );
		$args       = wp_parse_args( $args, $defaults );
		$collection = new ArrayCollection();
		$terms      = get_terms( $args );

		foreach ( $terms as $term ) {
			try {
				$collection->add( $this->factory( $term ) );
			} catch ( NotFoundException $e ) {
			}
		}

		return $collection;
	}

	/**
	 * @return ArrayCollection
	 */
	public function not_empty(): ArrayCollection {
		$args = array( 'hide_empty' => true );

		return $this->find( $args );
	}

	/**
	 * @param int $parent_id
	 *
	 * @return ArrayCollection
	 */
	public function child_of( int $parent_id ): ArrayCollection {
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
