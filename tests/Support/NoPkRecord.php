<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\Attributes\Column;
use Wpify\Model\Model;

/**
 * Invalid custom-table model: no primary key column.
 */
class NoPkRecord extends Model {
	#[Column( type: Column::VARCHAR )]
	public string $title = '';
}
