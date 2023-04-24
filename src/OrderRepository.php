<?php

declare( strict_types=1 );

namespace Wpify\Model;

use WC_Order;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

/**
 * Repository for Post models.
 *
 * @method Order create( array $data )
 */
class OrderRepository extends Repository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Order::class;
	}

	/**
	 * Returns the Post model by the WP_Post object, id, slug or URL.
	 *
	 * @param mixed $source
	 *
	 * @return ?Order
	 * @throws RepositoryNotInitialized
	 */
	public function get( mixed $source ): ?ModelInterface {
		$wc_order = null;
		$order    = null;

		if ( $source instanceof WC_Order ) {
			$wc_order = $source;
		}

		if ( ! $wc_order && is_numeric( $source ) ) {
			$wc_order = wc_get_order( $source );
		}

		if ( ! $wc_order && is_string( $source ) ) {
			$wc_order = wc_get_order_id_by_order_key( $source );
		}

		if ( $wc_order ) {
			$model_class = $this->model();
			$order       = new $model_class( $this->manager() );

			$order->source( $wc_order );
		}

		return $order;
	}

	/**
	 * Stores order into database.
	 *
	 * @param Order $model
	 *
	 * @return Order
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

		$model->refresh( wc_get_order( $result ) );

		return $model;
	}

	/**
	 * Deletes the given order.
	 *
	 * @param Order $model
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model ): bool {
		return boolval( $model->source()->delete( true ) );
	}


	/**
	 * Finds orders matching the given arguments.
	 *
	 * @param array $args
	 *
	 * @return Order[]
	 * @throws RepositoryNotInitialized
	 */
	public function find( array $args = array() ): array {
		$items      = wc_get_orders( $args );
		$collection = array();

		foreach ( $items as $item ) {
			$collection[] = $this->get( $item );
		}

		return $collection;
	}

	/**
	 * Finds all orders.
	 *
	 * @param array $args
	 *
	 * @return Order[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_all( array $args = array() ): array {
		$defaults = array(
			'limit' => - 1,
		);

		$args = wp_parse_args( $args, $defaults );

		return $this->find( $args );
	}

	/**
	 * Finds orders by ids.
	 *
	 * @param array $ids
	 *
	 * @return array
	 * @throws RepositoryNotInitialized
	 */
	public function find_by_ids( array $ids ): array {
		$orders = array();

		foreach ( $ids as $id ) {
			$orders[] = $this->get( $id );
		}

		return $orders;
	}
}
