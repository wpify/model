<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use stdClass;
use Wpify\Model\Exceptions\RepositoryNotFoundException;
use Wpify\Model\Manager;
use Wpify\Model\Post;
use Wpify\Model\PostRepository;
use Wpify\Model\Tests\Support\RelationProbeModel;
use Wpify\Model\Tests\Support\RelationProbeRepository;
use Wpify\Model\Tests\TestCase;
use Wpify\Model\UserRepository;

class ManagerTest extends TestCase {
	public function test_constructor_registers_default_repositories(): void {
		$repositories = $this->manager->get_repositories();

		$this->assertCount( 18, $repositories );
		$this->assertInstanceOf( PostRepository::class, $repositories[ PostRepository::class ] );
	}

	public function test_constructor_registers_passed_repositories_and_ignores_other_dependencies(): void {
		$manager = new Manager( new RelationProbeRepository(), new stdClass() );

		$this->assertInstanceOf(
			RelationProbeRepository::class,
			$manager->get_model_repository( RelationProbeModel::class )
		);
		$this->assertCount( 19, $manager->get_repositories() );
	}

	public function test_register_repository_wires_manager_and_model_relation(): void {
		$repository = new RelationProbeRepository();

		$this->manager->register_repository( $repository );

		$this->assertSame( $this->manager, $repository->manager() );
		$this->assertSame( $repository, $this->manager->get_model_repository( RelationProbeModel::class ) );
	}

	public function test_get_model_repository_returns_repository_for_model(): void {
		$this->assertInstanceOf( PostRepository::class, $this->manager->get_model_repository( Post::class ) );
	}

	public function test_get_model_repository_throws_for_unregistered_model(): void {
		$this->expectException( RepositoryNotFoundException::class );

		$this->manager->get_model_repository( stdClass::class );
	}

	public function test_get_repository_returns_registered_instance(): void {
		$first  = $this->manager->get_repository( UserRepository::class );
		$second = $this->manager->get_repository( UserRepository::class );

		$this->assertInstanceOf( UserRepository::class, $first );
		$this->assertSame( $first, $second );
	}

	public function test_get_repository_instantiates_and_caches_unregistered_class(): void {
		$repository = $this->manager->get_repository( RelationProbeRepository::class );

		$this->assertInstanceOf( RelationProbeRepository::class, $repository );
		$this->assertSame( $repository, $this->manager->get_repository( RelationProbeRepository::class ) );
	}

	public function test_get_repositories_is_keyed_by_class_name(): void {
		foreach ( $this->manager->get_repositories() as $class => $repository ) {
			$this->assertInstanceOf( $class, $repository );
		}
	}
}
