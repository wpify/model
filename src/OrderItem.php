<?php

declare( strict_types=1 );

namespace Wpify\Model;

use WC_Order_Item;
use Wpify\Model\Attributes\AccessorObject;

class OrderItem extends Model {
	/**
	 * Product ID.
	 */
	#[AccessorObject]
	public int $id = 0;

	/**
	 * Item type.
	 */
	#[AccessorObject]
	public string $type;

	/**
	 * Order item name.
	 */
	#[AccessorObject]
	public string $name;

	/**
	 * Order item quantity.
	 */
	#[AccessorObject]
	public string $quantity;

	/**
	 * Order item tax total.
	 */
	#[AccessorObject]
	public float $tax_total;

	/**
	 * Order item tax class.
	 */
	#[AccessorObject]
	public string $tax_class;

	/**
	 * Order item unit price tax included.
	 */
	public float $unit_price_tax_included;

	/**
	 * Order item unit price tax excluded.
	 */
	public float $unit_price_tax_excluded;

	/**
	 * Order item vat rate.
	 */
	public float $vat_rate;

	/**
	 * Order item vat rate id.
	 */
	public int $vat_rate_id;


	public ?WC_Order_Item $wc_order_item;

	/**
	 * Get WC Order Item.
	 *
	 * @return ?WC_Order_Item
	 */
	public function get_wc_order_item(): ?WC_Order_Item {
		return $this->source();
	}

	/**
	 * Get unit price tax included.
	 */
	public function get_unit_price_tax_included(): float {
		return $this->get_unit_price();
	}

	/**
	 * Get unit price.
	 *
	 * @param bool $inc_tax
	 *
	 * @return ?float
	 */
	public function get_unit_price( bool $inc_tax = true ): ?float {
		$total = null;

		if ( is_callable( array( $this->wc_order_item, 'get_total' ) ) && $this->wc_order_item->get_quantity() ) {
			if ( $inc_tax ) {
				$total = ( floatval( $this->wc_order_item->get_total() ) + floatval( $this->wc_order_item->get_total_tax() ) ) / floatval( $this->wc_order_item->get_quantity() );
			} else {
				$total = floatval( $this->wc_order_item->get_total() ) / floatval( $this->wc_order_item->get_quantity() );
			}
		}

		return $total;
	}

	/**
	 * Get unit price tax excluded.
	 *
	 * @return float
	 */
	public function get_unit_price_tax_excluded(): float {
		return $this->get_unit_price( false );
	}

	/**
	 * Get VAT rate
	 *
	 * @return float
	 */
	public function get_vat_rate(): float {
		$rate = 0;
		if ( $this->wc_order_item?->get_tax_status() == 'taxable' ) {
			foreach ( $this->wc_order_item->get_order()->get_items( 'tax' ) as $item_tax ) {
				$tax_data = $item_tax->get_data();
				if ( $tax_data['rate_id'] === $this->vat_rate_id ) {
					$rate = $tax_data['rate_percent'];
				}
				if ( ! $rate && $this->wc_order_item?->get_total_tax() ) {
					$rate = \round( $this->wc_order_item->get_total_tax() / ( $this->wc_order_item->get_total() / 100 ) );
				}
			}
		}

		return \floatval( $rate );
	}

	/**
	 * Get VAT rate id
	 *
	 * @return int
	 */
	public function get_vat_rate_id(): int {
		$rate_id = 0;
		if ( $this->wc_order_item?->get_tax_status() == 'taxable' ) {
			$item_data = $this->wc_order_item->get_data();
			foreach ( $item_data['taxes']['total'] as $item_tax_id => $item_tax_total ) {
				if ( $item_tax_total ) {
					$rate_id = $item_tax_id;
					break;
				}
			}
		}

		return $rate_id;
	}
}
