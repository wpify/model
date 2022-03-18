<?php

namespace Wpify\Model;

use Wpify\Model\Abstracts\AbstractModel;

/**
 * Class BasicPost
 * @package Wpify\Model
 * @property ProductRepository $_repository
 * @method \WC_Product source_object()
 */
class Product extends AbstractModel {
	/**
	 * Post ID.
	 * @since 3.5.0
	 * @var int
	 */
	public $id;

	/**
	 * ID of a post's parent post.
	 * @since 3.5.0
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Parent post
	 * @var self
	 */
	public $parent;

	/**
	 * Product type
	 * @var string
	 */
	public $type;

	/**
	 * Product name
	 * @var string
	 */
	public $name;

	/**
	 * Stock quantity
	 * @var int
	 */
	public $stock_quantity;

	/**
	 * Is in stock
	 * @var bool
	 */
	public $is_in_stock;

	/**
	 * Price
	 * @var float
	 */
	public $price;

	/**
	 * Image ID
	 * @var int
	 */
	public $image_id;

	/**
	 * WC_Product
	 */
	public $wc_product;


	/**
	 * @var string[][]
	 */
	protected $_props = array(
		'id'             => array( 'source' => 'object', 'source_name' => 'id' ),
		'parent_id'      => array( 'source' => 'object', 'source_name' => 'parent_id' ),
		'type'           => array( 'source' => 'object', 'source_name' => 'type' ),
		'name'           => array( 'source' => 'object', 'source_name' => 'name' ),
		'stock_quantity' => array( 'source' => 'object', 'source_name' => 'stock_quantity' ),
		'price'          => array( 'source' => 'object', 'source_name' => 'price' ),
		'image_id'       => array( 'source' => 'object', 'source_name' => 'image_id' ),

	);

	public function __construct( $object, ProductRepository $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'product';
	}

	/**
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public function fetch_meta( $key ) {
		return $this->source_object()->get_meta( $key, true );
	}

	/**
	 * @return ProductRepository
	 */
	public function model_repository(): ProductRepository {
		return $this->_repository;
	}

	public function get_wc_product() {
		return $this->source_object();
	}

	public function get_is_in_stock() {
        return $this->get_wc_product()->is_in_stock();
    }
}
