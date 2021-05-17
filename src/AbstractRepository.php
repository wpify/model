<?php

namespace WpifyModel;

/**
 * Class AbstractRepository
 *
 * @package WpifyModel
 */
abstract class AbstractRepository extends Base implements RepositoryInterface {
	protected $post_type;

	/**
	 * AbstractRepository constructor.
	 */
	public function __construct() {
		$this->post_type = $this->post_type();
		$this->initialize();
		$this->setup();
	}

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
	 * @param array $args
	 *
	 * @return mixed
	 */
	abstract public function all( array $args = array() );

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

	/**
	 * @param ModelInterface $model
	 *
	 * @return mixed
	 */
	abstract public function save( ModelInterface $model );

	/**
	 * @param $object
	 */
	abstract protected function factory( $object );
}
