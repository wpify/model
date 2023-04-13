<?php
declare( strict_types=1 );

namespace Wpify\Model\Interfaces;

interface StorageFactoryInterface {
	public function create(): StorageInterface;
}
