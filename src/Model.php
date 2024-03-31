<?php
declare( strict_types=1 );

namespace Wpify\Model;

use ArrayIterator;
use ReflectionAttribute;
use ReflectionObject;
use IteratorAggregate;
use ArrayAccess;
use Wpify\Model\Attributes\AccessorObject;
use Wpify\Model\Attributes\Meta;
use Wpify\Model\Exceptions\PropertyNotDefinedException;
use Wpify\Model\Exceptions\RepositoryNotFoundException;
use Wpify\Model\Interfaces\AccessorAttributeInterface;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Attributes\ReadOnlyProperty;
use Wpify\Model\Exceptions\ReadOnlyPropertyException;
use Wpify\Model\Interfaces\RepositoryInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

abstract class Model implements ModelInterface, IteratorAggregate, ArrayAccess {
	private Manager $_manager;
	private mixed $_source = null;
	private array $_props = array();
	private RepositoryInterface $_repository;
	private ReflectionObject $_reflection;

	/**
	 * Constructor for the model.
	 *
	 * @param Manager $repository_manager Repository manager.
	 * @param array $data Data to hydrate the model with.
	 *
	 * @throws RepositoryNotFoundException
	 */
	public function __construct( Manager $repository_manager, array $data = array() ) {
		$this->_manager    = $repository_manager;
		$this->_repository = $repository_manager->get_model_repository( get_class( $this ) );
		$reflection        = $this->reflection( new ReflectionObject( $this ) );

		foreach ( $reflection->getProperties() as $property ) {
			$type = $property->getType();

			$this->_props[ $property->getName() ] = array(
				'name'     => $property->getName(),
				'type'     => $type ? $type->getName() : 'string',
				'readonly' => false,
				'default'  => $this->{$property->getName()} ?? null,
				'allows_null' => $type ? $type->allowsNull() : false,
			);

			foreach ( $property->getAttributes( SourceAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF ) as $attribute ) {
				$this->_props[ $property->getName() ]['source'] = $attribute->newInstance();
			}

			foreach ( $property->getAttributes( ReadOnlyProperty::class, ReflectionAttribute::IS_INSTANCEOF ) as $attribute ) {
				$this->_props[ $property->getName() ]['readonly'] = true;
			}

			unset( $this->{$property->getName()} );
		}
	}

	/**
	 * Sets or gets the source for the model.
	 *
	 * @param mixed|null $source
	 *
	 * @return mixed
	 */
	public function source( mixed $source = null ): mixed {
		if ( $source !== null ) {
			$this->_source = $source;
		}

		return $this->_source;
	}

	/**
	 * Gets the properties metadata for the model.
	 *
	 * @return array
	 */
	public function props(): array {
		return $this->_props;
	}

	/**
	 * Gets the Manager.
	 *
	 * @return Manager
	 */
	public function manager(): Manager {
		return $this->_manager;
	}

	/**
	 * Gets or sets the reflection object for the model.
	 *
	 * @param ReflectionObject|null $reflection
	 *
	 * @return ReflectionObject
	 */
	public function reflection( ?ReflectionObject $reflection = null ): ReflectionObject {
		if ( $reflection ) {
			$this->_reflection = $reflection;
		}

		return $this->_reflection;
	}

	/**
	 * Delete cached values and optionally set a new source.
	 *
	 * This is useful when you want to refresh the model with new data.
	 *
	 * @param mixed|null $source
	 *
	 * @return void
	 */
	public function refresh( mixed $source = null ): void {
		foreach ( $this->_props as $prop => $value ) {
			unset( $this->_props[ $prop ]['value'] );
		}

		if ( $source ) {
			$this->source( $source );
		}
	}

	/**
	 * Getter for the model that resolves lazily its properties.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 * @throws PropertyNotDefinedException
	 */
	public function __get( string $name ) {
		if ( isset( $this->_props[ $name ]['value'] ) ) {
			return $this->_props[ $name ]['value'];
		}

		if ( ! isset( $this->_props[ $name ] ) ) {
			throw new PropertyNotDefinedException( 'Property ' . $name . ' in model ' . get_class( $this ) . ' is not defined' );
		}

		$value = $this->_repository->resolve_property( $this->_props[ $name ], $this );

		if ( is_null( $value ) ) {
			$value = $this->_props[ $name ]['default'];
		}

		$this->_props[ $name ]['value'] = $value;

		return $value;
	}

	/**
	 * Global setter for the model that checks if the property is readonly or call the setter if exists.
	 *
	 * @param string $name Property name.
	 * @param mixed $value Property value.
	 *
	 * @return void
	 * @throws ReadOnlyPropertyException
	 */
	public function __set( string $name, mixed $value ) {
		if ( $this->_props[ $name ]['readonly'] && ! empty( $this->_props[ $name ]['value'] ) ) {
			throw new ReadOnlyPropertyException( 'Cannot set readonly property ' . $name );
		}

		if ( method_exists( $this, 'set_' . $name ) ) {
			$this->{'set_' . $name}( $value );
		} else {
			$this->_props[ $name ]['value'] = $this->_repository->maybe_convert_to_type( $this->_props[ $name ], $value );
		}

		if ( isset( $this->_props[$name]['source'] ) && $this->_props[$name]['source'] instanceof AccessorAttributeInterface ) {
			$this->_props[$name]['source']->set($this, $name, $value);
		} elseif ( isset( $this->_props[$name]['source'] ) && $this->_props[$name]['source'] instanceof Meta ) {
			$this->_props[$name]['source']->set($this, $name, $value);
		}
	}

	/**
	 * Converts model to array.
	 *
	 * @param array $props Props to convert.
	 * @param array $recursive Props to convert recursively.
	 *
	 * @return array
	 */
	public function to_array( array $props = array(), array $recursive = array() ): array {
		if ( empty( $props ) ) {
			$props = array_keys( $this->_props );
		}

		$data = array();

		foreach ( $props as $prop ) {
			$data[ $prop ] = $this->$prop;

			if ( in_array( $prop, $recursive ) ) {
				if ( $this->$prop instanceof ModelInterface ) {
					$data[ $prop ] = $this->$prop->to_array( array(), $recursive );
				} elseif ( is_array( $this->$prop ) ) {
					$data[ $prop ] = array_map( function ( $item ) use ( $props, $recursive ) {
						if ( $item instanceof ModelInterface ) {
							return $item->to_array( array(), $recursive );
						}

						return $item;
					}, $this->$prop );
				}
			}
		}

		return $data;
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->to_array() );
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->_props[ $offset ]['value'] = $value;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->_props[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset( mixed $offset ): void {
		unset( $this->_props[ $offset ]['value'] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return array|false|mixed|null
	 */
	public function offsetGet( mixed $offset ): mixed {
		return isset( $this->_props[ $offset ] ) ? $this->$offset : null;
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
		unset( $this->_props[ $prop ]['value'] );
	}

}
