<?php

namespace Wpify\Model\Abstracts;

use ReflectionClass;
use ReflectionProperty;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\PostRepositoryInterface;
use Wpify\Model\Interfaces\RepositoryInterface;

/**
 * Class AbstractPostModel
 * @package Wpify\Model
 *
 * @property PostRepositoryInterface $_repository
 */
abstract class AbstractDbTableModel extends AbstractModel implements ModelInterface {
	public function __construct( $object, RepositoryInterface $repository ) {
		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties( ReflectionProperty::IS_PUBLIC);
		foreach ( $properties as $property ) {
			$key = $property->getName();
			$this->_props[$key] = array( 'source' => 'object', 'source_name' => $key );
		}

		parent::__construct($object,  $repository);
	}

	public function get_db_data() {
		$data = [];

		foreach (
			array_filter( $this->own_props(), function ( $prop ) {
				return $prop['source'] === 'object';
			} ) as $key => $item
		) {
			$data[ $key ] = $this->{$key};
		}

		return $data;
	}

	public function model_repository() {
		return $this->_repository;
	}

	static function meta_type() {
		return 'db';
	}
}
