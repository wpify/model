<?php

namespace WpifyModel;

use ReflectionClass;
use ReflectionProperty;

/**
 * Class AbstractModel
 * @package WpifyModel
 */
abstract class AbstractModel {
	/** @var int */
	public $id;

	/** @var object */
	private $object;

	/** @var array */
	private $props = array();

	/** @var string */
	private $meta_type;

	/** @var array */
	private $data = array();

	/**
	 * AbstractPost constructor.
	 *
	 * @param $object
	 */
	public function __construct( $object = null ) {
		$this->object    = $this->object( $object );
		$this->meta_type = $this->meta_type();
		$this->initialize();
	}

	abstract protected function object( $object );

	abstract protected function meta_type();

	private function initialize() {
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
				$props[ $name ]['type'] = $property->getType();
			}

			$object_vars = is_object( $this->object )
				? get_object_vars( $this->object )
				: array();

			if ( empty( $props[ $name ]['source'] ) ) {
				if ( method_exists( $this, 'get_' . $name ) ) {
					$props[ $name ]['source'] = 'getter';
					$props[ $name ]['getter'] = 'get_' . $name;
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
	 * Saves data from instance
	 *
	 * @return mixed
	 */
	abstract public function save();

	/**
	 * Refreshes the data in the instance
	 */
	public function refresh( $object = null ) {
		$this->object = $this->object( ! empty( $object ) ? $object : $this->object );
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
	 * @return array
	 */
	public function __serialize() {
		$data = array();

		foreach ( array_keys( $this->props ) as $prop ) {
			$data[ $prop ] = $this->$prop;
		}

		return $data;
	}

	/**
	 * @param $data
	 */
	public function __unserialize( $data ) {
		$this->object    = $this->object( $data['id'] ?? null );
		$this->meta_type = $this->meta_type();
		$this->data      = $data;
		$this->initialize();
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
		return get_metadata( $this->meta_type, $this->id, $key, true );
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 */
	public function set_meta( $key, $value ) {
		return update_metadata( $this->meta_type, $this->id, $key, $value );
	}

	/**
	 * @return array
	 */
	protected function get_props() {
		return $this->props;
	}

	/**
	 * @return object
	 */
	protected function get_object() {
		return $this->object;
	}

	/**
	 * @return array
	 */
	protected function get_data() {
		return $this->data;
	}
}
