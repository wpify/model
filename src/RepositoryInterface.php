<?php

namespace WpifyModel;

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
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all();

	/**
	 * @param AbstractModel $model
	 *
	 * @return mixed
	 */
	public function delete( ModelInterface $model );

	/**
	 * @param ModelInterface $model
	 *
	 * @return mixed
	 */
	public function save( ModelInterface $model );
}
