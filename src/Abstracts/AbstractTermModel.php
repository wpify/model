<?php

namespace WpifyModel\Abstracts;

abstract class AbstractTermModel extends AbstractModel {
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
	public $parent_id;

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

	abstract static function taxonomy(): string;

}
