<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\StorageInterface;

/**
 * Storage that stores nothing.
 */
class BlankStorage implements StorageInterface {

	public function get( mixed $key ): ?ModelInterface {
		return null;
	}

	public function save( mixed $key, mixed $value, array $other_keys = array() ): void {
	}

	public function delete( mixed $key ): void {
	}

	public function flush(): void {
	}
}
