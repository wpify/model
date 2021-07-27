<?php

namespace WpifyModel;

use WC_Order;
use WC_Order_Item;
use WpifyModel\Abstracts\AbstractPostModel;
use WpifyModel\Abstracts\AbstractRepository;
use WpifyModel\Exceptions\NotFoundException;
use WpifyModel\Exceptions\NotPersistedException;
use WpifyModel\Interfaces\ModelInterface;
use WpifyModel\Interfaces\PostModelInterface;
use WpifyModel\Interfaces\RepositoryInterface;
use WpifyModel\Interfaces\TermModelInterface;

/**
 * Class BasePostRepository
 * @package WpifyModel
 */
class OrderItemRepository extends AbstractRepository implements RepositoryInterface {
	/**
	 * @var string
	 */
	private $model;

	public function __construct( string $model = OrderItemLine::class ) {
		$this->model = $model;
	}

	/**
	 * @param ?object $object
	 */
	public function get( $object = null ) {
		return ! empty( $object ) ? $this->factory( $object ) : null;
	}

	/**
	 * @return AbstractPostModel[]
	 */
	public function all() {
		$args = array( 'limit' => - 1 );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() ) {
		$defaults = [];
		$args     = wp_parse_args( $args, $defaults );
		$items    = wc_get_orders( $args );

		$collection = array();

		foreach ( $items as $item ) {
			$collection[] = $this->factory( $item );
		}

		return $this->collection_factory( $collection );
	}

	/**
	 * @return AbstractPostModel
	 */
	public function create() {
		return $this->factory( null );
	}

	/**
	 * @param ModelInterface $model
	 * // TODO: Implement this
	 */
	public function save( $model ) {
		return $model;
	}

	/**
	 * @param $data
	 *
	 * @return WC_Order
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data ): WC_Order_Item {
		if ( is_object( $data ) && get_class( $data ) === $this::model() ) {
			$object = $data->source_object();
		} elseif ( $data instanceof WC_Order_Item ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WC_Order_Item();
		} elseif ( isset( $data->id ) ) {
			$object = new WC_Order_Item( $data->id );
		} else {
			$object = null;
		}

		if ( ! ( $object instanceof WC_Order_Item ) ) {
			throw new NotFoundException( 'The order was not found' );
		}

		return $object;
	}

	public function model(): string {
		return $this->model;
	}

	/**
	 * @param PostModelInterface $model
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function delete( PostModelInterface $model ) {
		return wc_delete_order_item( $model->id );
	}
}
