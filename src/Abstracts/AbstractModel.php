<?php

namespace WpifyModel\Abstracts;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use ReflectionClass;
use ReflectionProperty;
use WpifyModel\Interfaces\ModelInterface;

/**
 * Class AbstractModel
 * @package WpifyModel
 */
abstract class AbstractModel implements ModelInterface, IteratorAggregate, ArrayAccess {
	/** @var int */
	public $id;

	/** @var array */
	protected $_relations;

	/** @var array */
	protected $_data = array();
	/** @var array */
	protected $_props = array();
	/** @var object */
	private $_object;

	/**
	 * AbstractPost constructor.
	 *
	 * @param $object
	 * @param $relations
	 */
	public function __construct( $object, $relations ) {
		$this->_object    = $object;
		$this->_relations = $relations;

		$reflection = new ReflectionClass( $this );
		$properties = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );

		foreach ( $properties as $property ) {
			$name = $property->name;

			if ( ! isset( $this->_props[ $name ] ) ) {
				$this->_props[ $name ] = array(
					'name'    => $name,
					'changed' => false,
				);
			}

			if ( empty( $this->_props[ $name ]['type'] ) ) {
				$this->_props[ $name ]['type'] = method_exists( $property, 'getType' )
					? $property->getType()
					: null;
			}

			$object_vars = is_object( $this->_object )
				? get_object_vars( $this->_object )
				: array();

			if ( empty( $this->_props[ $name ]['source'] ) ) {
				if ( method_exists( $this, 'get_' . $name ) ) {
					$this->_props[ $name ]['source'] = 'getter';
					$this->_props[ $name ]['getter'] = 'get_' . $name;
				} elseif ( ! empty( $this->_relations[ $name ] ) ) {
					$this->_props[ $name ]['source'] = 'relation';
					$this->_props[ $name ]['fetch']  = $this->_relations[ $name ]['fetch'] ?? null;
					$this->_props[ $name ]['assign'] = $this->_relations[ $name ]['assign'] ?? null;
				} elseif ( array_key_exists( $name, $object_vars ) ) {
					$this->_props[ $name ]['source'] = 'object';
				} else {
					$this->_props[ $name ]['source'] = 'meta';
				}
			}

			if ( isset( $this->$name ) ) {
				$this->_props[ $name ]['default'] = $this->$name;
			}

			if ( empty( $this->_props[ $name ]['source_name'] ) ) {
				$this->_props[ $name ]['source_name'] = $name;
			}

			if ( empty( $this->_props[ $name ]['setter'] ) && method_exists( $this, 'set_' . $name ) ) {
				$this->_props[ $name ]['setter'] = 'set_' . $name;
			}

			// unset property, so it's handled by magic methods __get and __set
			unset( $this->$name );
		}
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->to_array() );
	}

	/**
	 * @param array $props
	 *
	 * @return array
	 */
	public function to_array( array $props = array() ): array {
		if ( empty( $props ) ) {
			$props = array_keys( $this->_props );
		}

		$data = array();

		foreach ( $props as $prop ) {
			$data[ $prop ] = $this->$prop;
		}

		return $data;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		$this->_data[ $offset ] = $value;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->_props[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->_data[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return array|false|mixed|null
	 */
	public function offsetGet( $offset ) {
		return isset( $this->_props[ $offset ] ) ? $this->$offset : null;
	}

	/**
	 * Refreshes the data in the instance
	 */
	public function refresh( $object = null ) {
		$this->_object = $object;
		$this->_data   = array();
	}

	/**
	 * @param $prop
	 *
	 * @return bool
	 */
	public function __isset( $prop ) {
		return isset( $this->_props[ $prop ] );
	}

	/**
	 * @param $prop
	 */
	public function __unset( $prop ) {
		unset( $this->_data[ $prop ] );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( isset( $this->_props[ $key ] ) ) {
			$prop = $this->_props[ $key ];

			if ( ! isset( $this->_data[ $key ] ) ) {
				$source_name = $prop['source_name'];

				if ( $prop['source'] === 'object' ) {
					if ( isset( $this->_object->$source_name ) ) {
						$this->_data[ $key ] = $this->_object->$source_name;
					} elseif ( isset( $prop['default'] ) ) {
						$this->_data[ $key ] = $prop['default'];
					} else {
						$this->_data[ $key ] = null;
					}
				} elseif ( $prop['source'] === 'meta' ) {
					$this->_data[ $key ] = $this->fetch_meta( $source_name );
				} elseif ( $prop['source'] === 'getter' ) {
					$getter              = $prop['getter'];
					$this->_data[ $key ] = $this->$getter();
				} elseif ( $prop['source'] === 'relation' && is_callable( $prop['fetch'] ) ) {
					$this->_data[ $key ] = $prop['fetch']( $this );
				} elseif ( isset( $prop['default'] ) ) {
					$this->_data[ $key ] = $prop['default'];
				} else {
					$this->_data[ $key ] = $this->$key ?? null;
				}
			}

			return $this->_data[ $key ];
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( string $key, $value ) {
		if ( isset( $this->_props[ $key ] ) ) {
			$this->_data[ $key ] = $value;

			$prop = $this->_props[ $key ];

			if ( ! empty( $prop['setter'] ) ) {
				$setter              = $prop['setter'];
				$this->_data[ $key ] = $this->$setter( $value );
			} else {
				$this->_data[ $key ] = $value;
			}

			$this->_props[ $key ]['changed'] = true;
		}
	}

	/**
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public function fetch_meta( $key ) {
		return get_metadata( $this::meta_type(), $this->id, $key, true );
	}

	/**
	 * @return mixed
	 */
	abstract static function meta_type();

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 */
	public function store_meta( $key, $value ) {
		return update_metadata( $this::meta_type(), $this->id, $key, $value );
	}

	/**
	 * @return array
	 */
	public function own_props(): array {
		return $this->_props;
	}

	/**
	 * @return object
	 */
	public function source_object(): object {
		return $this->_object;
	}
}
