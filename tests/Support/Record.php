<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\Attributes\Column;
use Wpify\Model\Model;

/**
 * Custom-table model used by CustomTableTest.
 */
class Record extends Model {
	#[Column( type: Column::BIGINT, unsigned: true, auto_increment: true, primary_key: true )]
	public int $id = 0;

	#[Column( type: Column::VARCHAR, params: 100 )]
	public string $title = '';

	#[Column( type: Column::INT )]
	public int $quantity = 0;

	#[Column( type: Column::VARCHAR, nullable: true, unique: true )]
	public ?string $code = null;

	#[Column( type: Column::JSON )]
	public array $payload = array();

	#[Column( type: Column::TIMESTAMP, default: 'CURRENT_TIMESTAMP', on_update: 'CURRENT_TIMESTAMP' )]
	public string $updated_at = '';
}
