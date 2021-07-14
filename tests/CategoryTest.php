<?php

use Brain\Monkey;
use Codeception\Test\Unit;
use WpifyModel\CategoryRepository;

class CategoryTest extends Unit {
	public function testCreateCategory() {
		$term_name = 'name';
		$term_id   = 1;

		Monkey\Functions\expect( 'wp_insert_term' )
			->once()
			->with(
				$term_name,
				CategoryRepository::taxonomy(),
				array(
					'name'        => $term_name,
					'description' => '',
					'parent'      => null,
					'slug'        => '',
					'term_group'  => '',
				),
			)
			->andReturn( array(
				'term_id' => $term_id,
			) );

		$result_term           = Mockery::mock( 'WP_Term' );
		$result_term->term_id  = $term_id;
		$result_term->name     = $term_name;
		$result_term->taxonomy = CategoryRepository::taxonomy();

		Brain\Monkey\Functions\expect( 'get_term_by' )
			->once()
			->with( 'ID', $term_id, CategoryRepository::taxonomy() )
			->andReturn( $result_term );

		$repository  = new CategoryRepository();
		$model       = $repository->create();
		$model->name = $term_name;
		$repository->save( $model );

		$this->assertEquals( $term_id, $model->id );
		$this->assertEquals( $term_name, $model->name );
	}

	public function testUpdateCategory() {
		$term_name = 'name';
		$term_id   = 1;

		Monkey\Functions\expect( 'wp_update_term' )
			->once()
			->with(
				$term_id,
				CategoryRepository::taxonomy(),
				array(
					'name'        => $term_name,
					'description' => '',
					'parent'      => null,
					'slug'        => '',
					'term_group'  => '',
				),
			)
			->andReturn( array(
				'term_id' => $term_id,
			) );

		$result_term           = Mockery::mock( 'WP_Term' );
		$result_term->term_id  = $term_id;
		$result_term->name     = $term_name;
		$result_term->taxonomy = CategoryRepository::taxonomy();

		Brain\Monkey\Functions\expect( 'get_term_by' )
			->twice()
			->with( 'ID', $term_id, CategoryRepository::taxonomy() )
			->andReturn( $result_term );

		$repository  = new CategoryRepository();
		$model       = $repository->get( $term_id );
		$model->name = $term_name;
		$repository->save( $model );

		$this->assertEquals( $term_id, $model->id );
		$this->assertEquals( $term_name, $model->name );
	}

	protected function _before() {
		Monkey\setUp();
	}

	protected function _after() {
		Monkey\tearDown();
	}
}
