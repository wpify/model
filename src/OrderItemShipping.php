<?php

namespace Wpify\Model;

use WC_Product;
use Wpify\Model\Abstracts\AbstractOrderItemModel;

/**
 * Class Order
 * @package Wpify\Model
 * @property ProductRepository $_repository
 * @method \WC_Order_Item_Shipping source_object()
 */
class OrderItemShipping extends AbstractOrderItemModel {
	public $method_id;
	public $instance_id;
	public $method_title;
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
		'method_id'    => array( 'source' => 'object', 'source_name' => 'method_id' ),
		'instance_id'  => array( 'source' => 'object', 'source_name' => 'instance_id' ),
		'method_title' => array( 'source' => 'object', 'source_name' => 'method_title' ),
	);
}
