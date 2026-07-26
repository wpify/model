<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use WC_Order_Item_Shipping;
use WC_Tax;
use Wpify\Model\Order;
use Wpify\Model\OrderItem;
use Wpify\Model\OrderItemFee;
use Wpify\Model\OrderItemFeeRepository;
use Wpify\Model\OrderItemLine;
use Wpify\Model\OrderItemLineRepository;
use Wpify\Model\OrderItemRepository;
use Wpify\Model\OrderItemShipping;
use Wpify\Model\OrderItemShippingRepository;
use Wpify\Model\OrderRepository;
use Wpify\Model\Tests\Fixtures\WooFixtures;
use Wpify\Model\Tests\TestCase;

class OrdersTest extends TestCase {
	private function orders(): OrderRepository {
		return $this->repo( OrderRepository::class );
	}

	private function make_wc_order( array $product_props = array(), int $quantity = 2 ): \WC_Order {
		return WooFixtures::order( array(), WooFixtures::simple_product( $product_props ), $quantity );
	}

	public function test_model_returns_order_class(): void {
		$this->assertSame( Order::class, $this->orders()->model() );
	}

	public function test_get_by_id_object_and_model(): void {
		$wc_order = $this->make_wc_order();
		$id       = $wc_order->get_id();

		$by_id = $this->orders()->get( $id );
		$this->assertInstanceOf( Order::class, $by_id );
		$this->assertSame( $id, $by_id->id );

		$by_object = $this->orders()->get( wc_get_order( $id ) );
		$this->assertSame( $id, $by_object->id );

		$this->assertSame( $by_id, $this->orders()->get( $by_id ) );

		$this->assertFalse( (bool) $this->orders()->get( 999999 ) );
	}

	/**
	 * get() by order key assigns the numeric id as the model source, so the
	 * returned model fatals on property access.
	 *
	 * @see https://github.com/wpify/model/issues/38
	 */
	public function test_get_by_order_key(): void {
		$this->markTestSkipped( 'OrderRepository::get() by order key sets an int source — https://github.com/wpify/model/issues/38' );
	}

	public function test_save_updates_order(): void {
		$order = $this->orders()->get( $this->make_wc_order()->get_id() );

		$order->get_wc_order()->set_billing_first_name( 'Updated' );
		$this->orders()->save( $order );

		$this->assertSame( 'Updated', wc_get_order( $order->id )->get_billing_first_name() );
	}

	public function test_save_inserts_order_with_attached_source(): void {
		$order = $this->orders()->create();
		$order->source( new \WC_Order() );

		$saved = $this->orders()->save( $order );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertInstanceOf( \WC_Order::class, wc_get_order( $saved->id ) );
	}

	public function test_delete_removes_order(): void {
		$order = $this->orders()->get( $this->make_wc_order()->get_id() );

		$this->assertTrue( $this->orders()->delete( $order ) );
		$this->assertFalse( (bool) wc_get_order( $order->id ) );
	}

	public function test_find_find_all_and_find_by_ids(): void {
		$first  = $this->make_wc_order()->get_id();
		$second = $this->make_wc_order()->get_id();

		$found = $this->orders()->find( array( 'limit' => -1 ) );
		$this->assertCount( 2, $found );

		$this->assertCount( 2, $this->orders()->find_all() );

		$by_ids = $this->orders()->find_by_ids( array( $first, $second ) );
		$this->assertSame( array( $first, $second ), array_map( fn( $o ) => $o->id, $by_ids ) );
	}

	public function test_order_items_relations_and_get_items(): void {
		$wc_order = $this->make_wc_order();

		$fee = new \WC_Order_Item_Fee();
		$fee->set_name( 'Handling' );
		$fee->set_total( '5' );
		$wc_order->add_item( $fee );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_instance_id( '3' );
		$shipping->set_total( '10' );
		$wc_order->add_item( $shipping );
		$wc_order->save();

		$order = $this->orders()->get( $wc_order->get_id() );

		$this->assertCount( 1, $order->line_items );
		$this->assertInstanceOf( OrderItemLine::class, $order->line_items[0] );

		$this->assertCount( 1, $order->shipping_items );
		$this->assertInstanceOf( OrderItemShipping::class, $order->shipping_items[0] );

		$this->assertCount( 1, $order->fee_items );
		$this->assertInstanceOf( OrderItemFee::class, $order->fee_items[0] );

		$this->assertCount( 3, $order->get_items() );
	}

	public function test_get_wc_order_returns_source(): void {
		$order = $this->orders()->get( $this->make_wc_order()->get_id() );

		$this->assertInstanceOf( \WC_Abstract_Order::class, $order->get_wc_order() );
	}

	public function test_get_weight_sums_line_item_products(): void {
		// A 0.5 kg product bought twice.
		update_option( 'woocommerce_weight_unit', 'kg' );
		$order = $this->orders()->get( $this->make_wc_order( array( 'weight' => '0.5' ), 2 )->get_id() );

		// get_weight() sums per line item (not per quantity).
		$this->assertSame( 0.5, $order->get_weight( 'kg' ) );
		$this->assertSame( 500.0, $order->get_weight( 'g' ) );
	}

	public function test_has_shipping_method(): void {
		$wc_order = $this->make_wc_order();

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_instance_id( '3' );
		$wc_order->add_item( $shipping );
		$wc_order->save();

		$order = $this->orders()->get( $wc_order->get_id() );

		$this->assertTrue( $order->has_shipping_method( 'flat_rate:3' ) );
		$this->assertTrue( $order->has_shipping_method( array( 'other:1', 'flat_rate:3' ) ) );
		$this->assertFalse( $order->has_shipping_method( 'local_pickup:1' ) );
		$this->assertFalse( $order->has_shipping_method( array( 'local_pickup:1' ) ) );
	}

	public function test_order_item_repository_get_create_and_models(): void {
		$repository = $this->repo( OrderItemRepository::class );

		$this->assertSame( OrderItem::class, $repository->model() );
		$this->assertSame( OrderItemLine::class, $this->repo( OrderItemLineRepository::class )->model() );
		$this->assertSame( OrderItemFee::class, $this->repo( OrderItemFeeRepository::class )->model() );
		$this->assertSame( OrderItemShipping::class, $this->repo( OrderItemShippingRepository::class )->model() );

		$wc_order = $this->make_wc_order();
		$items    = array_values( $wc_order->get_items() );

		$by_object = $repository->get( $items[0] );
		$this->assertInstanceOf( OrderItem::class, $by_object );

		// get() by id instantiates WC_Order_Item directly, which WC 9.9+
		// flags — https://github.com/wpify/model/issues/41
		$this->setExpectedIncorrectUsage( 'WC_Order_Item::__construct' );
		$by_id = $repository->get( $items[0]->get_id() );
		$this->assertInstanceOf( OrderItem::class, $by_id );

		$this->assertSame( $by_object, $repository->get( $by_object ) );

		$created = $repository->create();
		$this->assertInstanceOf( OrderItem::class, $created );

		// find/find_all/find_by_ids are documented as not implemented.
		$this->assertSame( array(), $repository->find() );
		$this->assertSame( array(), $repository->find_all() );
		$this->assertSame( array(), $repository->find_by_ids( array( 1 ) ) );
	}

	public function test_order_item_save_persists_changes(): void {
		$wc_order = $this->make_wc_order();
		$items    = array_values( $wc_order->get_items() );

		$repository = $this->repo( OrderItemLineRepository::class );
		$item       = $repository->get( $items[0] );

		$item->name = 'Renamed line';
		$repository->save( $item );

		$fresh = array_values( wc_get_order( $wc_order->get_id() )->get_items() );
		$this->assertSame( 'Renamed line', $fresh[0]->get_name() );
	}

	/**
	 * save() refreshes the model with the item id instead of the item object,
	 * so the saved model fatals on later property access.
	 *
	 * @see https://github.com/wpify/model/issues/40
	 */
	public function test_order_item_save_keeps_model_usable(): void {
		$this->markTestSkipped( 'OrderItemRepository::save() refreshes with the item id — https://github.com/wpify/model/issues/40' );
	}

	public function test_order_item_delete(): void {
		$wc_order = $this->make_wc_order();
		$items    = array_values( $wc_order->get_items() );

		$repository = $this->repo( OrderItemLineRepository::class );
		$item       = $repository->get( $items[0] );

		$this->assertTrue( $repository->delete( $item ) );
		$this->assertCount( 0, wc_get_order( $wc_order->get_id() )->get_items() );
	}

	public function test_order_item_prices_and_vat(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);

		// 2 × 50 net → 100 net + 20 tax.
		$wc_order = $this->make_wc_order( array( 'regular_price' => '50' ), 2 );
		$items    = array_values( $wc_order->get_items() );

		$item = $this->repo( OrderItemLineRepository::class )->get( $items[0] );

		$this->assertSame( 60.0, $item->get_unit_price_tax_included() );
		$this->assertSame( 60.0, $item->get_unit_price() );
		$this->assertSame( 50.0, $item->get_unit_price_tax_excluded() );
		$this->assertSame( 50.0, $item->get_unit_price( false ) );

		$this->assertInstanceOf( \WC_Order_Item::class, $item->get_wc_order_item() );

		$this->assertGreaterThan( 0, $item->get_vat_rate_id() );
		$this->assertSame( 20.0, $item->get_vat_rate() );
	}
}
