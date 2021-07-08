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
	protected $relations;

	/** @var object */
	private $object;

	/** @var array */
	private $props = array();

	/** @var array */
	private $data = array();

	/**
	 * AbstractPost constructor.
	 *
	 * @param $object
	 * @param $relations
	 */
	public function __construct( $object, $relations ) {
		$this->object    = $object;
		$this->relations = $relations;

		$reflection = new ReflectionClass( $this );
		$properties = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );
		$props      = $this->props( $this->props );

		foreach ( $properties as $property ) {
			$name = $property->name;

			if ( ! isset( $props[ $name ] ) ) {
				$props[ $name ] = array(
					'name' => $name,
				);
			}

			if ( empty( $props[ $name ]['type'] ) ) {
				$props[ $name ]['type'] = method_exists( $property, 'getType' )
					? $property->getType()
					: null;
			}

			$object_vars = is_object( $this->object )
				? get_object_vars( $this->object )
				: array();

			if ( empty( $props[ $name ]['source'] ) ) {
				if ( method_exists( $this, 'get_' . $name ) ) {
					$props[ $name ]['source'] = 'getter';
					$props[ $name ]['getter'] = 'get_' . $name;
				} elseif ( ! empty( $this->relations[ $name ] ) ) {
					$props[ $name ]['source'] = 'relation';
				} elseif ( array_key_exists( $name, $object_vars ) ) {
					$props[ $name ]['source'] = 'object';
				} else {
					$props[ $name ]['source'] = 'meta';
				}
			}

			if ( isset( $this->$name ) ) {
				$props[ $name ]['default'] = $this->$name;
			}

			if ( empty( $props[ $name ]['source_name'] ) ) {
				$props[ $name ]['source_name'] = $name;
			}

			if ( empty( $props[ $name ]['setter'] ) && method_exists( $this, 'set_' . $name ) ) {
				$props[ $name ]['setter'] = 'set_' . $name;
			}

			// unset property, so it's handled by magic methods __get and __set
			unset( $this->$name );
		}

		$this->props = $props;
	}

	/**
	 * @param array $props
	 *
	 * @return array
	 */
	protected function props( array $props = array() ): array {
		return $props;
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
			$props = array_keys( $this->props );
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
		$this->data[ $offset ] = $value;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->props[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return array|false|mixed|null
	 */
	public function offsetGet( $offset ) {
		return isset( $this->props[ $offset ] ) ? $this->$offset : null;
	}

	/**
	 * Refreshes the data in the instance
	 */
	public function refresh( $object = null ) {
		$this->object = $object;
		$this->data   = array();
	}

	/**
	 * @param $prop
	 *
	 * @return bool
	 */
	public function __isset( $prop ) {
		return isset( $this->props[ $prop ] );
	}

	/**
	 * @param $prop
	 */
	public function __unset( $prop ) {
		unset( $this->props[ $prop ] );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( isset( $this->props[ $key ] ) ) {
			$prop = $this->props[ $key ];

			if ( ! isset( $this->data[ $key ] ) ) {
				$source_name = $prop['source_name'];

				if ( $prop['source'] === 'object' ) {
					if ( isset( $this->object->$source_name ) ) {
						$this->data[ $key ] = $this->object->$source_name;
					} elseif ( isset( $prop['default'] ) ) {
						$this->data[ $key ] = $prop['default'];
					} else {
						$this->data[ $key ] = null;
					}
				} elseif ( $prop['source'] === 'meta' ) {
					$this->data[ $key ] = $this->get_meta( $source_name );
				} elseif ( $prop['source'] === 'getter' ) {
					$getter             = $prop['getter'];
					$this->data[ $key ] = $this->$getter();
				} elseif ( $prop['source'] === 'relation' ) {
					$this->data[ $key ] = $this->get_relation( $key );
				} elseif ( isset( $prop['default'] ) ) {
					$this->data[ $key ] = $prop['default'];
				} else {
					$this->data[ $key ] = $this->$key;
				}
			}

			return $this->data[ $key ];
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( string $key, $value ) {
		$this->data[ $key ] = $value;

		if ( isset( $this->props[ $key ] ) ) {
			$prop = $this->props[ $key ];

			if ( ! empty( $prop['setter'] ) ) {
				$setter             = $prop['setter'];
				$this->data[ $key ] = $this->$setter( $value );
			} else {
				$this->data[ $key ] = $value;
			}
		}
	}

	/**
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public function get_meta( $key ) {
		return get_metadata( $this::meta_type(), $this->id, $key, true );
	}

	/**
	 * @return mixed
	 */
	abstract static function meta_type();

	/**
	 * @param $key
	 *
	 * @return null
	 */
	protected function get_relation( $key ) {
		if ( ! empty( $this->relations[ $key ] ) && is_array( $this->relations[ $key ] ) ) {
			$relation = $this->relations[ $key ];
			$callback = null;
			$args     = array();

			foreach ( $relation as $item ) {
				if ( empty( $callback ) ) {
					if ( ! is_callable( $item ) ) {
						return null;
					}

					$callback = $item;
				} else {
					if ( ! isset( $this->$item ) ) {
						return null;
					}

					$args[] = $this->$item;
				}
			}

			return $callback( ...$args );
		}

		return null;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 */
	public function set_meta( $key, $value ) {
		return update_metadata( $this::meta_type(), $this->id, $key, $value );
	}

	/**
	 * @return array
	 */
	public function get_props(): array {
		return $this->props;
	}

	/**
	 * @return object
	 */
	public function get_object(): object {
		return $this->object;
	}

	/**
	 * @return array
	 */
	protected function get_data(): array {
		return $this->data;
	}
}
