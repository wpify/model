<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Repository;

class RelationProbeRepository extends Repository {
	public function model(): string {
		return RelationProbeModel::class;
	}

	public function get( mixed $source ): ?ModelInterface {
		return null;
	}

	public function save( ModelInterface $model ): ModelInterface {
		return $model;
	}

	public function delete( ModelInterface $model, bool $force_delete = true ): bool {
		return true;
	}

	public function find( array $args = array() ): array {
		return array();
	}

	public function find_by_ids( array $ids ): array {
		return array();
	}

	public function find_all( array $args = array() ): array {
		return array();
	}
}
