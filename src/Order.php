<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractModel;
use WpifyModel\Relations\OrderItemsRelation;

/**
 * Class Order
 * @package WpifyModel
 * @property OrderRepository $_repository
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
	 * @var OrderItemLine[]
	 */
	public $line_items;

	/**
	 * Shipping items
	 * @var OrderItemShipping[]
	 */
	public $shipping_items;

	/**
	 * Fee items
	 * @var OrderItemFee[]
	 */
	public $fee_items;

	public $items;

	public $weight;

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
	 * @return OrderRepository
	 */
	public function model_repository(): OrderRepository {
		return $this->_repository;
	}

	/**
	 * Get order Line items
	 * @return OrderItemsRelation
	 */
	public function line_items_relation() {
		return new OrderItemsRelation( $this, $this->model_repository()->get_item_repository(OrderItemLine::class), 'line_item' );
	}

	/**
	 * Get order Shipping items
	 * @return OrderItemsRelation
	 */
	public function shipping_items_relation() {
		return new OrderItemsRelation( $this, $this->model_repository()->get_item_repository(OrderItemShipping::class), 'shipping' );
	}


	/**
	 * Get order Shipping items
	 * @return OrderItemsRelation
	 */
	public function fee_items_relation() {
		return new OrderItemsRelation( $this, $this->model_repository()->get_item_repository(OrderItemFee::class), 'fee' );
	}

	/**
	 * @return array|OrderItem[]
	 */
	public function get_items() {
		return array_merge( $this->line_items, $this->shipping_items, $this->fee_items );
	}

	/**
	 * @return mixed
	 */
	public function get_weight(string $unit = 'kg')
	{
		if ($this->weight) {
			return $this->weight;
		}
		$wc_weight_unit = get_option('woocommerce_weight_unit');
		$this->weight = 0;
		foreach ($this->line_items as $item) {
			$prod = $item->product;
			if (\method_exists($prod, 'get_weight')) {
				if ($prod->get_weight()) {
					$this->weight += $prod->get_weight();
				}
			}
		}
		if ($wc_weight_unit === 'g' && $unit === 'kg') {
			$this->weight = $this->weight / 1000;
		}
		if ($wc_weight_unit === 'kg' && $unit === 'g') {
			$this->weight = $this->weight * 1000;
		}
		return $this->weight;
	}
	/**
	 * @param string|[] $shipping_method_id Expects ID in method_id:instance_id format
	 */
	public function has_shipping_method($shipping_method_ids)
	{
		$methods = [];
		foreach ($this->shipping_items as $item) {
			$methods[] = \sprintf('%s:%s', $item->get_method_id(), $item->get_instance_id());
		}
		if (\is_array($shipping_method_ids)) {
			$found = \false;
			foreach ($methods as $method) {
				if (\in_array($method, $shipping_method_ids)) {
					$found = \true;
					break;
				}
			}
			return $found;
		}
		return \in_array($shipping_method_ids, $methods);
	}

}