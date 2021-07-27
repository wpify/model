<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractModel;
use WpifyModel\Relations\OrderItemsRelation;

/**
 * Class Order
 * @package WpifyModel
 * @property ProductRepository $_repository
 * @method \WC_Order source_object()
 */
class Order extends AbstractModel {
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
	 * Line items
	 * @var OrderItem[]
	 */
	public $line_items;

	/**
	 * Shipping items
	 * @var OrderItem[]
	 */
	public $shipping_items;

	/**
	 * Fee items
	 * @var OrderItem[]
	 */
	public $fee_items;

	public $items;

	/**
	 * @var string[][]
	 */
	protected $_props = array(
		'id'        => array( 'source' => 'object', 'source_name' => 'id' ),
		'parent_id' => array( 'source' => 'object', 'source_name' => 'parent_id' ),
	);

	public function __construct( $object, OrderRepository $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'order';
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
	public function model_repository(): OrderRepository {
		return $this->_repository;
	}

	/**
	 * Get order Line items
	 * @return array
	 */
	public function line_items_relation() {
		return new OrderItemsRelation( $this, $this->model_repository()->get_item_repository(), 'line_item' );
	}

	/**
	 * Get order Shipping items
	 * @return array
	 */
	public function shipping_items_relation() {
		return new OrderItemsRelation( $this, $this->model_repository()->get_item_repository(), 'shipping' );
	}


	/**
	 * Get order Shipping items
	 * @return array
	 */
	public function fee_items_relation() {
		return new OrderItemsRelation( $this, $this->model_repository()->get_item_repository(), 'fee' );
	}

	public function get_items() {
		var_dump($this->line_items);
		var_dump($this->shiping_items);
		var_dump($this->fee_items);
		return array_merge( $this->line_items, $this->shipping_items, $this->fee_items );
	}

}
