<?php

namespace Wpify\Model\Abstracts;

use Wpify\Model\OrderItemRepository;

/**
 * Class Order
 * @package Wpify\Model
 * @property OrderItemRepository $_repository
 * @method \WC_Order_Item source_object()
 */
abstract class AbstractOrderItemModel extends AbstractModel {
	/**
	 * Post ID.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $id;

	public $type;
	public $name;
	public $unit_price_tax_included;
	public $unit_price_tax_excluded;
	public $quantity;
	public $vat_rate;
	public $tax_total;
	public $tax_class;


	/**
	 * @var string[][]
	 */
	protected $_props = array(
		'id'        => array( 'source' => 'object', 'source_name' => 'id' ),
		'type'      => array( 'source' => 'object', 'source_name' => 'type' ),
		'name'      => array( 'source' => 'object', 'source_name' => 'name' ),
		'quantity'  => array( 'source' => 'object', 'source_name' => 'quantity' ),
		'tax_total' => array( 'source' => 'object', 'source_name' => 'tax_total' ),
		'tax_class' => array( 'source' => 'object', 'source_name' => 'tax_class' ),
	);

	public function __construct( $object, OrderItemRepository $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'order_item';
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
	 * @return OrderItemRepository
	 */
	public function model_repository(): OrderItemRepository {
		return $this->_repository;
	}

	/**
	 * @return mixed
	 */
	public function get_unit_price_tax_included() {
		static $price;
		if ( ! $price ) {
			$price = $this->get_unit_price();
		}

		return $price;
	}

	public function get_unit_price( $inc_tax = true ) {
		if ( \is_callable( array( $this->source_object(), 'get_total' ) ) && $this->source_object()->get_quantity() ) {
			if ( $inc_tax ) {
				$total = ( $this->source_object()->get_total() + $this->source_object()->get_total_tax() ) / $this->source_object()->get_quantity();
			} else {
				$total = \floatval( $this->source_object()->get_total() ) / $this->source_object()->get_quantity();
			}
		}

		return $total;
	}

	/**
	 * @return mixed
	 */
	public function get_unit_price_tax_excluded() {
		static $price;
		if ( ! $price ) {
			$price = $this->get_unit_price( false );
		}

		return $price;
	}

	/**
	 * Get VAT rate
	 * @return float
	 */
	public function get_vat_rate(): float {
		$rate = 0;

		if ( $this->source_object()->get_tax_status() == 'taxable' ) {
			$item_data = $this->source_object()->get_data();
			foreach ( $item_data['taxes']['total'] as $item_tax_id => $item_tax_total ) {
				$used_item_tax_id = $item_tax_total ? $item_tax_id : null;
				foreach ( $this->source_object()->get_order()->get_items( 'tax' ) as $item_tax ) {
					$tax_data = $item_tax->get_data();
					if ( $tax_data['rate_id'] === $used_item_tax_id ) {
						$rate = $tax_data['rate_percent'];
					}
				}
			}
			if ( ! $rate ) {
				if ( $this->source_object()->get_total_tax() ) {
					$rate = \round( $this->source_object()->get_total_tax() / ( $this->source_object()->get_total() / 100 ) );
				}
			}
		}

		return floatval( $rate );
	}
}
