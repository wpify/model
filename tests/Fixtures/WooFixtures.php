<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Fixtures;

use WC_Order;
use WC_Product_Simple;

/**
 * Hand-rolled WooCommerce fixtures over public CRUD.
 * WooCommerce's own WC_Helper_* classes are not distributed, so the few
 * fixture shapes this suite needs live here.
 */
class WooFixtures {
	public static function simple_product( array $props = array() ): WC_Product_Simple {
		$product = new WC_Product_Simple();
		$product->set_props(
			array_merge(
				array(
					'name'          => 'Test product',
					'regular_price' => '10',
					'weight'        => '',
				),
				$props
			)
		);
		$product->save();

		return $product;
	}

	public static function order( array $args = array(), ?WC_Product_Simple $product = null, int $quantity = 1 ): WC_Order {
		$order = wc_create_order( array_merge( array( 'status' => 'pending' ), $args ) );

		if ( $product ) {
			$order->add_product( $product, $quantity );
		}

		$order->set_billing_email( 'customer@example.com' );
		$order->calculate_totals();
		$order->save();

		return $order;
	}

	/**
	 * @return int The created term id.
	 */
	public static function product_cat( string $name = 'Test category' ): int {
		$term = wp_insert_term( $name, 'product_cat' );

		return $term['term_id'];
	}
}
