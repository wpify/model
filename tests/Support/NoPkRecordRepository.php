<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\CustomTableRepository;

class NoPkRecordRepository extends CustomTableRepository {
	public function model(): string {
		return NoPkRecord::class;
	}

	public function table_name(): string {
		return 'wpify_test_no_pk';
	}
}
