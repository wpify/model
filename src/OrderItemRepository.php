<?php

declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

/**
 * Repository for Order Item models.
 *
 * @internal This class is not part of the public API and may change at any time.
 */
class OrderItemRepository extends Repository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return OrderItem::class;
	}

	/**
	 * Returns the Post model by the WP_Post object, id, slug or URL.
	 *
	 * @param mixed $source
	 *
	 * @return ?OrderItemLine
	 * @throws RepositoryNotInitialized
	 */
	public function get( mixed $source ): ?ModelInterface {
		$wc_order_item = null;
		$item          = null;

		if ( $source instanceof \WC_Order_Item ) {
			$wc_order_item = $source;
		}

		if ( ! $wc_order_item && is_numeric( $source ) ) {
			$wc_order_item = new \WC_Order_Item( $source );
		}

		if ( $wc_order_item ) {
			$model_class = $this->model();
			$item        = new $model_class( $this->manager() );

			$item->source( $wc_order_item );
		}

		return $item;
	}

	/**
	 * Creates a new order item model.
	 *
	 * If the repository has multiple post types or no post types, this will throw an exception.
	 *
	 * @param array $data Data to set on the model.
	 *
	 * @return OrderItem
	 * @throws RepositoryNotInitialized
	 */
	public function create( array $data = array() ): ModelInterface {
		/** @var OrderItem $model */
		return parent::create( $data );
	}

	/**
	 * Stores post into database.
	 *
	 * @param OrderItem $model
	 *
	 * @return OrderItem
	 * @throws CouldNotSaveModelException
	 */
	public function save( ModelInterface $model ): ModelInterface {
		foreach ( $model->props() as $prop ) {
			if ( empty( $prop['source'] ) || $prop['readonly'] ) {
				continue;
			}

			if ( method_exists( $model, 'persist_' . $prop['name'] ) ) {
				$model->{'persist_' . $prop['name']}( $prop['value'] );
			}
		}

		$result = $model->source()->save();

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message() );
		}

		$model->refresh( $result );

		return $model;
	}

	/**
	 * Deletes the given product.
	 *
	 * @param OrderItem $model
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model ): bool {
		return boolval( $model->source()->delete( true ) );
	}


	/**
	 * Not implemented, returns an empty array.
	 *
	 * @internal This method is not part of the public API and won't be implemented.
	 *
	 * @param array $args
	 *
	 * @return OrderItemLine[]
	 */
	public function find( array $args = array() ): array {
		return array();
	}

	/**
	 * Not implemented, returns an empty array.
	 *
	 * @internal This method is not part of the public API and won't be implemented.
	 *
	 * @param array $args
	 *
	 * @return OrderItemLine[]
	 */
	public function find_all( array $args = array() ): array {
		return array();
	}

	/**
	 * Not implemented, returns an empty array.
	 *
	 * @internal This method is not part of the public API and won't be implemented.
	 *
	 * @param array $ids
	 *
	 * @return OrderItemLine[]
	 */
	public function find_by_ids( array $ids ): array {
		return array();
	}
}
