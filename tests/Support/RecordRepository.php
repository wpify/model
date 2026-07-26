<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\CustomTableRepository;

class RecordRepository extends CustomTableRepository {
	public function model(): string {
		return Record::class;
	}

	public function table_name(): string {
		return 'wpify_test_records';
	}
}
