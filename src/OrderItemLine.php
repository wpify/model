<?php

namespace Wpify\Model;

use WC_Order_Item_Product;
use Wpify\Model\Abstracts\AbstractOrderItemModel;

/**
 * Class Order
 * @package Wpify\Model
 * @property ProductRepository $_repository
 * @method WC_Order_Item_Product source_object()
 */
class OrderItemLine extends AbstractOrderItemModel {
	public $product;
	public $product_id;
	public $variation_id;

	/**
	 * @var string[][]
	 */
	protected $_props = array(
		'id'           => array( 'source' => 'object', 'source_name' => 'id' ),
		'type'         => array( 'source' => 'object', 'source_name' => 'type' ),
		'name'         => array( 'source' => 'object', 'source_name' => 'name' ),
		'quantity'     => array( 'source' => 'object', 'source_name' => 'quantity' ),
		'tax_total'    => array( 'source' => 'object', 'source_name' => 'tax_total' ),
		'tax_class'    => array( 'source' => 'object', 'source_name' => 'tax_class' ),
		'product'      => array( 'source' => 'object', 'source_name' => 'product' ),
		'product_id'   => array( 'source' => 'object', 'source_name' => 'product_id' ),
		'variation_id' => array( 'source' => 'object', 'source_name' => 'variation_id' ),
	);


}
