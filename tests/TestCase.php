<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests;

use WP_UnitTestCase;
use Wpify\Model\Manager;

/**
 * Base test case: a fresh Manager with all default repositories per test.
 */
abstract class TestCase extends WP_UnitTestCase {
	protected Manager $manager;

	public function set_up(): void {
		parent::set_up();

		$this->manager = new Manager();
	}

	/**
	 * Shorthand for fetching a repository instance from the manager.
	 */
	protected function repo( string $repository_class ) {
		return $this->manager->get_repository( $repository_class );
	}
}
