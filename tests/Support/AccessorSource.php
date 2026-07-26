<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

/**
 * Source object with getter/setter methods for AccessorObject tests.
 */
class AccessorSource {
	public mixed $stored = 'initial';

	public function get_acc(): mixed {
		return $this->stored;
	}

	public function set_acc( mixed $value ): static {
		$this->stored = $value;

		return $this;
	}
}
