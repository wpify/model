<?php

declare( strict_types=1 );

namespace Wpify\Model;

/**
 * Repository for Order Item Shipping models.
 */
class OrderItemShippingRepository extends OrderItemRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return OrderItemShipping::class;
	}
}
