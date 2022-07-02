<?php

namespace Wpify\Model\Abstracts;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use ReflectionClass;
use ReflectionProperty;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\RepositoryInterface;
use Wpify\Model\PHPDocParser;

/**
 * Class AbstractModel
 * @package Wpify\Model
 */
abstract class AbstractModel implements ModelInterface, IteratorAggregate, ArrayAccess {
	/** @var int */
	public $id;

	/** @var RepositoryInterface */
	protected $_repository;

	/** @var array */
	protected $_data = array();

	/** @var array */
	protected $_props = array();

	/** @var object */
	protected $_object;

	/**
	 * AbstractPost constructor.
	 *
	 * @param                     $object
	 * @param RepositoryInterface $repository
	 */
	public function __construct( $object, RepositoryInterface $repository ) {
		$this->_object     = $object;
		$this->_repository = $repository;

		$reflection = new ReflectionClass( $this );
		$properties = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );
		// TODO: Cache parsing the doc comment
		$parser = new PHPDocParser();

		foreach ( $properties as $property ) {
			$name = $property->name;

			if ( ! isset( $this->_props[ $name ] ) ) {
				$this->_props[ $name ] = array(
					'name'    => $name,
					'changed' => false,
				);
			}

			if ( $property->getDocComment() ) {
				$parsed_doc = $parser->parse( get_class( $this ), 'properties', $property->getDocComment(), $name );
			} else {
				$parsed_doc = null;
			}

			if ( empty( $this->_props[ $name ]['type'] ) ) {
				if ( method_exists( $property, 'getType' ) && $property->getType() ) {
					$this->_props[ $name ]['type'] = $property->getType()->getName();
				}
			}

			if ( empty( $this->_props[ $name ]['type'] ) && $parsed_doc ) {
				foreach ( $parsed_doc->children as $child ) {
					if ( isset( $child->name ) && $child->name === '@var' ) {
						$this->_props[ $name ]['type'] = isset( $child->value ) ? strval( $child->value ) : null;
						break;
					}
				}
			}

			$object_vars = is_object( $this->_object )
				? get_object_vars( $this->_object )
				: array();

			if ( empty( $this->_props[ $name ]['source'] ) ) {
				if ( method_exists( $this, 'get_' . $name ) ) {
					$this->_props[ $name ]['getter'] = array( $this, 'get_' . $name );
				}

				if ( method_exists( $this, 'set_' . $name ) ) {
					$this->_props[ $name ]['setter'] = array( $this, 'set_' . $name );
				}

				if ( method_exists( $this, $name . '_relation' ) ) {
					$this->_props[ $name ]['source']   = 'relation';
					$method                            = $name . '_relation';
					$this->_props[ $name ]['relation'] = $this->$method();
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

			if ( $parsed_doc ) {
				foreach ( $parsed_doc->children as $child ) {
					if ( isset( $child->name ) && $child->name === '@readonly' ) {
						$this->_props[ $name ]['readonly'] = true;
						break;
					}
				}
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

				if ( isset( $prop['getter'] ) && is_callable( $prop['getter'] ) ) {
					$getter              = $prop['getter'];
					$this->_data[ $key ] = $getter();
				} elseif ( $prop['source'] === 'relation' ) {
					$relation            = $prop['relation'];
					$this->_data[ $key ] = $relation->fetch();
				} elseif ( $prop['source'] === 'object' ) {
					$getter = 'get_' . $source_name;

					if ( $this->_object && method_exists( $this->_object, $getter ) ) {
						$this->_data[ $key ] = $this->_object->$getter();
					} elseif ( isset( $this->_object->$source_name ) ) {
						$this->_data[ $key ] = $this->_object->$source_name;
					} elseif ( isset( $prop['default'] ) ) {
						$this->_data[ $key ] = $prop['default'];
					} else {
						$this->_data[ $key ] = null;
					}
				} elseif ( $prop['source'] === 'meta' ) {
					$this->_data[ $key ] = $this->fetch_meta( $source_name );
				} elseif ( isset( $prop['default'] ) ) {
					$this->_data[ $key ] = $prop['default'];
				} else {
					$this->_data[ $key ] = $this->$key ?? null;
				}

				if ( ! empty( $prop['type'] ) ) {
					$this->_data[ $key ] = $this->maybe_convert_to_type( $prop['type'], $this->_data[ $key ] );
				}
			}

			return $this->_data[ $key ];
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function __set( string $key, $value ) {
		if ( isset( $this->_props[ $key ] ) ) {
			$this->_data[ $key ] = $value;

			$prop = $this->_props[ $key ];

			if ( isset( $prop['setter'] ) && is_callable( $this, $prop['setter'] ) ) {
				$setter              = $prop['setter'];
				$this->_data[ $key ] = $setter( $value );
			} else {
				$this->_data[ $key ] = $value;
			}

			if ( $prop['source'] === 'object' && $this->_object && \property_exists( $this->_object, $prop['source_name'] ) ) {
				$this->_object->{$prop['source_name']} = $this->_data[ $key ];
			}

			$this->_props[ $key ]['changed'] = true;

			$after_set_hook = sprintf( 'after_%s_set', $key );

			if ( method_exists( $this, $after_set_hook ) ) {
				$this->$after_set_hook();
			}
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

	private function maybe_convert_to_type( $type, $value ) {
		if ( ( $type === 'int' || $type === 'integer' ) && ! is_int( $value ) ) {
			return intval( $value );
		}

		if ( $type === 'float' && ! is_float( $value ) ) {
			return floatval( $value );
		}

		if ( $type === 'string' && ! is_string( $value ) ) {
			return strval( $value );
		}

		if ( ( $type === 'bool' || $type === 'boolean' ) && ! is_bool( $value ) ) {
			return boolval( $value );
		}

		if ( $type === 'array' && is_string( $value ) ) {
			return json_decode( $value, true, 512, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		if ( $type === 'object' && is_string( $value ) ) {
			return json_decode( $value, false, 512, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		return $value;
	}

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
