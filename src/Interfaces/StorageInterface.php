<?php
declare( strict_types=1 );

namespace Wpify\Model\Interfaces;

interface StorageInterface {
	public function get( mixed $key ): ?ModelInterface;

	public function save( mixed $key, mixed $value, array $other_keys = array() ): void;

	public function delete( mixed $key ): void;

	public function flush(): void;
}
