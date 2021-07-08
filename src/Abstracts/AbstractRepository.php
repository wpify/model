<?php

namespace WpifyModel\Abstracts;

use WpifyModel\Interfaces\RepositoryInterface;

/**
 * Class AbstractRepository
 *
 * @package WpifyModel
 */
abstract class AbstractRepository implements RepositoryInterface {
	/** @var array */
	protected $relations;

	/**
	 * AbstractRepository constructor.
	 *
	 * @param array $relations
	 */
	public function __construct( array $relations = array() ) {
		$this->relations = $relations;
	}

	/**
	 * @param $object
	 *
	 * @return mixed
	 */
	protected function factory( $object ) {
		$class = $this::model();

		return new $class( $this->resolve_object( $object ), $this->relations );
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	abstract protected function resolve_object( $data );

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	protected function collection_factory( array $data ) {
		return $data;
	}
}
