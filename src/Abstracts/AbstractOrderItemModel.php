<?php

namespace WpifyModel\Abstracts;

use WpifyModel\OrderItemRepository;

/**
 * Class Order
 * @package WpifyModel
 * @property OrderItemRepository $_repository
 * @method \WC_Product source_object()
 */
abstract class AbstractOrderItemModel extends AbstractModel {
	/**
	 * Post ID.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $id;

	/**
	 * @var string[][]
	 */
	protected $_props = array(
		'id'        => array( 'source' => 'object', 'source_name' => 'id' ),
	);

	public function __construct( $object, OrderItemRepository $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'order_item';
	}

	/**
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public function fetch_meta( $key ) {
		return $this->source_object()->get_meta( $key, true );
	}

	/**
	 * @return ProductRepository
	 */
	public function model_repository(): ProductRepository {
		return $this->_repository;
	}
}
