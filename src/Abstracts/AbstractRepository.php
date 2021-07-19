<?php

namespace WpifyModel\Abstracts;

use WpifyModel\Interfaces\RepositoryInterface;

/**
 * Class AbstractRepository
 *
 * @package WpifyModel
 */
abstract class AbstractRepository implements RepositoryInterface {
	/**
	 * @param $object
	 *
	 * @return mixed
	 */
	protected function factory( $object ) {
		$class = $this::model();

		return new $class( $this->resolve_object( $object ), $this );
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
