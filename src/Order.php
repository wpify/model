<?php

declare( strict_types=1 );

namespace Wpify\Model;

use WC_Abstract_Order;
use WC_Order;
use Wpify\Model\Attributes\AccessorObject;
use Wpify\Model\Attributes\OrderItemsRelation;
use Wpify\Model\Attributes\ReadOnlyProperty;

class Order extends Model {
	/**
	 * Product ID.
	 */
	#[AccessorObject]
	public int $id = 0;

	/**
	 * Product Parent ID.
	 */
	#[AccessorObject]
	public int $parent_id = 0;

	/**
	 * Order weight.
	 */
	#[AccessorObject]
	public string $weight = '';

	/**
	 * Order total.
	 */
	#[AccessorObject]
	public string $total = '';

	/**
	 * Order key.
	 */
	#[AccessorObject]
	public string $order_key = '';

	/**
	 * WC Order.
	 */
	#[ReadOnlyProperty]
	public ?WC_Abstract_Order $wc_order = null;

	/**
	 * Order line items.
	 */
	#[OrderItemsRelation]
	#[ReadOnlyProperty]
	public array $line_items = array();

	/**
	 * Order shipping items.
	 */
	#[OrderItemsRelation( 'shipping' )]
	#[ReadOnlyProperty]
	public array $shipping_items = array();

	/**
	 * Order shipping items.
	 */
	#[OrderItemsRelation( 'fee' )]
	#[ReadOnlyProperty]
	public array $fee_items = array();

	#[ReadOnlyProperty]
	public array $items = array();


	/**
	 * Get WC Order.
	 *
	 * @return WC_Abstract_Order|null
	 */
	public function get_wc_order(): WC_Abstract_Order|null {
		return $this->source();
	}

	/**
	 * Get total weight of order.
	 *
	 * @param string $unit
	 *
	 * @return float
	 */
	public function get_weight( string $unit = 'g' ): float {
		// TODO: Cache this
		$wc_weight_unit = get_option( 'woocommerce_weight_unit' );
		$weight         = 0;

		foreach ( $this->line_items as $item ) {
			$prod = $item->product;

			if ( method_exists( $prod, 'get_weight' ) ) {
				if ( $prod->get_weight() ) {
					$weight += $prod->get_weight();
				}
			}
		}
		if ( $wc_weight_unit === 'g' && $unit === 'kg' ) {
			$weight = $weight / 1000;
		}
		if ( $wc_weight_unit === 'kg' && $unit === 'g' ) {
			$weight = $weight * 1000;
		}

		return $weight;
	}

	/**
	 * Check if order has a shipping method.
	 *
	 * @param $shipping_method_ids
	 *
	 * @return bool
	 */
	public function has_shipping_method( $shipping_method_ids ): bool {
		$methods = array();

		foreach ( $this->shipping_items as $item ) {
			$methods[] = sprintf( '%s:%s', $item->method_id, $item->instance_id );
		}

		if ( is_array( $shipping_method_ids ) ) {
			$found = false;

			foreach ( $methods as $method ) {
				if ( in_array( $method, $shipping_method_ids ) ) {
					$found = true;

					break;
				}
			}

			return $found;
		}

		return in_array( $shipping_method_ids, $methods );
	}

	/**
	 * Get order items.
	 *
	 * @return array
	 */
	public function get_items(): array {
		return array_merge( $this->line_items, $this->shipping_items, $this->fee_items );
	}

}
