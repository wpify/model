<?php

namespace WpifyModel;

/**
 * Class AbstractRepository
 *
 * @package WpifyModel
 */
abstract class AbstractRepository implements RepositoryInterface {
	protected $post_type;

	/**
	 * AbstractRepository constructor.
	 */
	public function __construct() {
		$this->post_type = $this->post_type();
	}

	/**
	 * @param $object
	 */
	abstract protected function factory( $object );

	/**
	 * @return string
	 */
	abstract protected function post_type(): string;

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	abstract public function find( array $args = array() );

	/**
	 * @param mixed $object
	 *
	 * @return mixed
	 */
	abstract public function get( $object = null );

	/**
	 * @param AbstractModel $model
	 *
	 * @return mixed
	 */
	abstract public function delete( ModelInterface $model );
}
