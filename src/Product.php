<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractModel;
use WpifyModel\Interfaces\PostRepositoryInterface;

/**
 * Class BasicPost
 * @package WpifyModel
 */
class Product extends AbstractModel {
	/**
	 * @return string
	 */
	static function post_type(): string {
		return 'product';
	}

	/**
	 * Post ID.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $id;

	/**
	 * ID of a post's parent post.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Parent post
	 *
	 * @var self
	 */
	public $parent;

	protected $_props = array(
		'id'               => array( 'source' => 'object', 'source_name' => 'id' ),
		'parent_id'        => array( 'source' => 'object', 'source_name' => 'parent_id' ),
	);

	public function __construct( $object, PostRepositoryInterface $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'product';
	}
}
