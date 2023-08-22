<?php
declare( strict_types=1 );

namespace Wpify\Model\Interfaces;

use Wpify\Model\Manager;

interface RepositoryInterface {
	public function manager( ?Manager $manager = null ): Manager;

	public function model(): string;

	public function resolve_property( array $property, ModelInterface $model );

	public function maybe_convert_to_type( $type, $value, $default );

	public function get( mixed $source ): ?ModelInterface;

	public function create( array $data ): ModelInterface;

	public function save( ModelInterface $model ): ModelInterface;

	public function delete( ModelInterface $model ): bool;

	public function find( array $args = [] ): array;

	public function find_by_ids( array $ids ): array;

	public function find_all( array $args = [] ): array;
}
