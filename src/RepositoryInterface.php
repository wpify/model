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
	public function all( array $args = array() );

	/**
	 * @param AbstractModel $model
	 *
	 * @return mixed
	 */
	public function delete( ModelInterface $model );
}
