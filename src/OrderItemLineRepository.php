<?php

declare( strict_types=1 );

namespace Wpify\Model;

/**
 * Repository for Post models.
 */
class OrderItemLineRepository extends OrderItemRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return OrderItemLine::class;
	}
}
