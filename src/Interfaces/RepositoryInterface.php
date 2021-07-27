<?php

namespace WpifyModel\Interfaces;

/**
 * Class AbstractRepository
 *
 * @package WpifyModel
 */
interface RepositoryInterface {
	/**
	 * @return class-string
	 */
	public function model(): string;

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
	 * @param PostModelInterface $model
	 *
	 * @return mixed
	 */
	public function delete( PostModelInterface $model );

	/**
	 * @param $model
	 *
	 * @return mixed
	 */
	public function save( $model );
}
