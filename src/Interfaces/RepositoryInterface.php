<?php

namespace WpifyModel\Interfaces;

/**
 * Class AbstractRepository
 *
 * @package WpifyModel
 */
interface RepositoryInterface {
	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() );

	/**
	 * @param mixed $object
	 *
	 * @return mixed
	 */
	public function get( $object = null );

	/**
	 * @return mixed
	 */
	public function all();

	/**
	 * @param ModelInterface $model
	 *
	 * @return mixed
	 */
	public function delete( $model );

	/**
	 * @param ModelInterface $model
	 *
	 * @return mixed
	 */
	public function save( $model );

	/**
	 * @return class-string
	 */
	static function model(): string;
}
