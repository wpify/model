<?php

namespace WpifyModel;

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
	 * @param array $args
	 *
	 * @return mixed
	 */
	abstract public function find( array $args = array() );

	/**
	 * @return mixed
	 */
	abstract public function all();

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
	public function save( ModelInterface $model ) {
		return $model->save();
	}

	/**
	 * @param $object
	 *
	 * @return mixed
	 */
	protected function factory( $object ) {
		$class = $this::model();

		return new $class( $object, $this->relations );
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	protected function collection_factory( array $data ) {
		return $data;
	}

	/**
	 * @return class-string
	 */
	abstract static function model(): string;
}
