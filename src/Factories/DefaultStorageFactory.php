<?php
declare( strict_types=1 );

namespace Wpify\Model\Factories;

use Wpify\Model\Interfaces\StorageFactoryInterface;
use Wpify\Model\Interfaces\StorageInterface;
use Wpify\Model\MemoryStorage;

class DefaultStorageFactory implements StorageFactoryInterface {
	public function create(): StorageInterface {
		return new MemoryStorage();
	}
}
