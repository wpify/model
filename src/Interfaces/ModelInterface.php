<?php
declare( strict_types=1 );

namespace Wpify\Model\Interfaces;

use ReflectionObject;
use Wpify\Model\Manager;

interface ModelInterface {
	public function __construct( Manager $repository_manager, array $data = array() );

	public function manager(): Manager;

	public function source( mixed $source = null );

	public function reflection( ?ReflectionObject $reflection = null ): ReflectionObject;

	public function props(): array;

	public function refresh( mixed $source = null ): void;

	public function to_array( array $props = array(), array $recursive = array() ): array;
}
