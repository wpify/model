<?php

declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\RepositoryInterface;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;
use Wpify\Model\Interfaces\StorageInterface;

abstract class Repository implements RepositoryInterface {
	private ?Manager $manager = null;

	/**
	 * Gets or sets the Manager.
	 *
	 * @param  Manager|null  $manager
	 *
	 * @return Manager
	 * @throws RepositoryNotInitialized
	 */
	public function manager( ?Manager $manager = null ): Manager {
		if ( $manager ) {
			$this->manager = $manager;
		}

		if ( ! $this->manager ) {
			throw new RepositoryNotInitialized( 'Repository not initialized, use ' . Manager::class . '->register_repository( ' . get_class( $this ) . ' ) to initialize or add.' );
		}

		return $this->manager;
	}

	/**
	 * Gets the model class for the repository.
	 * @return string
	 */
	abstract public function model(): string;

	/**
	 * Resolve the property value from the source.
	 *
	 * @param  array  $property
	 * @param  Post  $model
	 *
	 * @return mixed
	 */
	public function resolve_property( array $property, ModelInterface $model ): mixed {
		$value = null;

		if ( method_exists( $model, 'get_' . $property['name'] ) ) {
			$value = $model->{'get_' . $property['name']}();
		} elseif ( isset( $property['source'] ) && $property['source'] instanceof SourceAttributeInterface ) {
			$value = $property['source']->get( $model, $property['name'] );
		}

		return $this->maybe_convert_to_type( $property, $value );
	}

	/**
	 * Convert the value to the type.
	 *
	 * @param $property
	 * @param $value
	 *
	 * @return mixed
	 */
	public function maybe_convert_to_type( $property, $value): mixed {
		if ( empty( $value ) && $property['allows_null'] ) {
			return $property['default'];
		}

		$type = $property['type'];

		if ( $type === 'int' || $type === 'integer' ) {
			return intval( $value );
		}

		if ( $type === 'float' ) {
			return floatval( $value );
		}

		if ( $type === 'string' ) {
			return strval( $value );
		}

		if ( ( $type === 'bool' || $type === 'boolean' ) && in_array( $value, array( 'true', 'false' ), true ) ) {
			return $value === 'true';
		}

		if ( ( $type === 'bool' || $type === 'boolean' ) ) {
			return boolval( $value );
		}

		if ( $type === 'array' && is_string( $value ) ) {
			return json_decode( $value, true, 512, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		if ( $type === 'array' ) {
			return (array) $value;
		}

		if ( $type === 'object' && is_string( $value ) ) {
			return json_decode( $value, false, 512, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		if ( $type === 'object' ) {
			return (object) $value;
		}

		return $value;
	}

	/**
	 * Creates a new model, optionally setting the properties.
	 *
	 * @param  array  $data
	 *
	 * @return ModelInterface
	 * @throws RepositoryNotInitialized
	 */
	public function create( array $data = array() ): ModelInterface {
		$model_class = $this->model();
		$post        = new $model_class( $this->manager() );

		foreach ( $data as $key => $value ) {
			if ( isset( $post->$key ) ) {
				$post->$key = $value;
			}
		}

		return $post;
	}
}
