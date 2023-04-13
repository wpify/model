<?php

declare( strict_types=1 );

namespace Wpify\Model;

/**
 * Repository for Post models.
 */
class OrderItemFeeRepository extends OrderItemRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return OrderItemFee::class;
	}
}
