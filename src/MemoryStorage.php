<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\StorageInterface;

/**
 * Storage that stores values in memory.
 */
class MemoryStorage implements StorageInterface {
	/**
	 * Alternative keys to the main key.
	 */
	private array $keys = array();

	/**
	 * Values stored in the storage by key.
	 */
	private array $values = array();

	/**
	 * Get a value from the storage by the primary key or secondary keys.
	 *
	 * @param mixed $key
	 *
	 * @return ModelInterface|null
	 */
	public function get( mixed $key ): ?ModelInterface {
		if ( isset( $this->keys[ $key ] ) ) {
			return $this->values[ $this->keys[ $key ] ];
		} elseif ( isset( $this->values[ $key ] ) ) {
			return $this->values[ $key ];
		}

		return null;
	}

	/**
	 * Save a value to the storage by the primary key and any secondary keys.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @param array $other_keys
	 *
	 * @return void
	 */
	public function save( mixed $key, mixed $value, array $other_keys = array() ): void {
		$this->values[ $key ] = $value;

		foreach ( $other_keys as $index_key => $other_key ) {
			$this->keys[ $other_key ] = $key;
		}
	}

	/**
	 * Delete a value from the storage by the primary key.
	 *
	 * @param mixed $key
	 *
	 * @return void
	 */
	public function delete( mixed $key ): void {
		if ( isset( $this->keys[ $key ] ) ) {
			unset( $this->values[ $this->keys[ $key ] ] );

			foreach ( $this->keys as $other_key => $primary_key ) {
				if ( $primary_key === $key ) {
					unset( $this->keys[ $other_key ] );
				}
			}
		}
	}

	/**
	 * Flush the storage.
	 *
	 * @return void
	 */
	public function flush(): void {
		$this->keys   = array();
		$this->values = array();
	}
}
