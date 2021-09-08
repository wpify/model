<?php

namespace Wpify\Model\Abstracts;

use Wpify\Model\Interfaces\PostModelInterface;
use Wpify\Model\Interfaces\RepositoryInterface;
use Wpify\Model\Interfaces\TermModelInterface;
use Wpify\Model\Interfaces\TermRepositoryInterface;
use Wpify\Model\Relations\TermChildTermsRelation;
use Wpify\Model\Relations\TermParentTermRelation;
use Wpify\Model\Relations\TermPostsRelation;
use Wpify\Model\Relations\TermTopLevelParentTermRelation;

/**
 * Class AbstractTermModel
 * @package Wpify\Model\Abstracts
 *
 * @property TermRepositoryInterface $_repository
 */
abstract class AbstractTermModel extends AbstractModel implements TermModelInterface {
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
	 * Top level parent term
	 *
	 * @var self
	 */
	public $top_level_parent;

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

	protected $_props = array(
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
	);

	public function __construct( $object, TermRepositoryInterface $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'term';
	}

	/**
	 * @return TermRepositoryInterface
	 */
	public function model_repository(): TermRepositoryInterface {
		return $this->_repository;
	}

	/**
	 * @return TermParentTermRelation
	 */
	public function parent_relation(): TermParentTermRelation {
		return new TermParentTermRelation( $this, $this->model_repository() );
	}

	protected function after_parent_set() {
		$this->parent_id = $this->parent->id ?? null;
	}

	protected function after_parent_id_set() {
		unset( $this->parent );
	}

	/**
	 * @return TermChildTermsRelation
	 */
	public function children_relation(): TermChildTermsRelation {
		return new TermChildTermsRelation( $this, $this->model_repository() );
	}

	/**
	 * @return TermTopLevelParentTermRelation
	 */
	public function top_level_parent_relation(): TermTopLevelParentTermRelation
	{
		return new TermTopLevelParentTermRelation( $this, $this->model_repository() );
	}
}
