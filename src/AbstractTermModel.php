<?php

namespace WpifyModel;

use WP_Term;

abstract class AbstractTermModel extends AbstractModel implements ModelInterface {
	/**
	 * Term ID.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $id;

	/**
	 * The term's name.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $name = '';

	/**
	 * The term's slug.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $slug = '';

	/**
	 * The term's term_group.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $group = '';

	/**
	 * Term Taxonomy ID.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $taxonomy_id = 0;

	/**
	 * The term's taxonomy name.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $taxonomy_name = '';

	/**
	 * The term's description.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $description = '';

	/**
	 * ID of a term's parent term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Parent term
	 *
	 * @var self
	 */
	public $parent;

	/**
	 * Children of the term
	 *
	 * @var self[]
	 */
	public $children;

	/**
	 * Cached object count for this term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $count = 0;

	/**
	 * Stores the term object's sanitization level.
	 *
	 * Does not correspond to a database field.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $filter = 'raw';

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'term';
	}

	public function save() {
		$args = array(
			'name'        => $this->name,
			'description' => $this->description,
			'parent'      => $this->parent_id,
			'slug'        => $this->slug,
			'term_group'  => $this->group,
		);

		if ( $this->id > 0 ) {
			$result = wp_update_term( $this->id, $this->taxonomy_name, $args );
		} else {
			$result = wp_insert_term( $this->name, $this->taxonomy_name, $args );
		}

		if ( ! is_wp_error( $result ) ) {
			// save the meta data
			foreach ( $this->get_props() as $key => $prop ) {
				if ( $prop['source'] === 'meta' ) {
					$this->set_meta( $prop['source_name'], $this->$key );
				}
			}

			$this->refresh( $result );
		}

		return $result;
	}

	/**
	 * @param array $props
	 *
	 * @return array
	 */
	protected function props( array $props = array() ): array {
		return array_merge( $props, array(
			'id'            => array( 'source' => 'object', 'source_name' => 'term_id' ),
			'name'          => array( 'source' => 'object', 'source_name' => 'name' ),
			'slug'          => array( 'source' => 'object', 'source_name' => 'slug' ),
			'group'         => array( 'source' => 'object', 'source_name' => 'term_group' ),
			'taxonomy_id'   => array( 'source' => 'object', 'source_name' => 'term_taxonomy_id' ),
			'taxonomy_name' => array( 'source' => 'object', 'source_name' => 'taxonomy' ),
			'description'   => array( 'source' => 'object', 'source_name' => 'description' ),
			'parent_id'     => array( 'source' => 'object', 'source_name' => 'parent' ),
			'count'         => array( 'source' => 'object', 'source_name' => 'count' ),
			'filter'        => array( 'source' => 'object', 'source_name' => 'filter' ),
		) );
	}

	/**
	 * @param $object
	 *
	 * @return ?WP_Term
	 * @throws NotFoundException
	 */
	protected function object( $object = null ): ?WP_Term {
		$new_object = null;

		if ( $object instanceof WP_Term ) {
			$new_object = $object;
		} elseif ( is_null( $object ) ) {
			$new_object = new WP_Term( (object) array(
				'term_id'          => $this->id,
				'name'             => $this->name,
				'slug'             => $this->slug,
				'term_group'       => $this->group,
				'term_taxonomy_id' => $this->taxonomy_id,
				'taxonomy'         => $this::taxonomy(),
				'description'      => $this->description,
				'parent'           => $this->parent_id,
				'count'            => $this->count,
				'filter'           => $this->filter,
			) );
		} elseif ( isset( $object->id ) ) {
			$new_object = get_term_by( 'ID', $object->id, $this::taxonomy() );
		} elseif ( is_string( $object ) ) {
			$new_object = get_term_by( 'slug', $object, $this::taxonomy() );
		} elseif ( is_int( $object ) ) {
			$new_object = get_term_by( 'ID', $object, $this::taxonomy() );
		} elseif ( is_array( $object ) && isset( $object['field'] ) && isset( $object['value'] ) ) {
			$new_object = get_term_by( $object['field'], $object['value'], $this::taxonomy() );
		}

		if ( ! is_object( $new_object ) ) {
			throw new NotFoundException( 'The term was not found' );
		}

		return $new_object;
	}

	abstract static function taxonomy(): string;

}
