<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractModel;

/**
 * Class BasicPost
 * @package WpifyModel
 * @method \WC_Product source_object()
 */
class Product extends AbstractModel {
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

	/**
	 * Product type
	 *
	 * @var string
	 */
	public $type;

	protected $_props = array(
		'id'               => array( 'source' => 'object', 'source_name' => 'id' ),
		'parent_id'        => array( 'source' => 'object', 'source_name' => 'parent_id' ),
		'type'        => array( 'source' => 'object', 'source_name' => 'type' ),
	);

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'product';
	}

	public function __construct( $object, ProductRepository $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public function fetch_meta( $key ) {
		return $this->source_object()->get_meta($key, true);
	}

}
