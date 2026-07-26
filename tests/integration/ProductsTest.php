<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use WC_Tax;
use Wpify\Model\Exceptions\IncorrectRepositoryException;
use Wpify\Model\Product;
use Wpify\Model\ProductCat;
use Wpify\Model\ProductCatRepository;
use Wpify\Model\ProductRepository;
use Wpify\Model\Tests\Fixtures\WooFixtures;
use Wpify\Model\Tests\TestCase;

class ProductsTest extends TestCase {
	private function products(): ProductRepository {
		return $this->repo( ProductRepository::class );
	}

	public function test_model_returns_product_class(): void {
		$this->assertSame( Product::class, $this->products()->model() );
	}

	public function test_get_by_id_object_sku_slug_and_model(): void {
		$wc_product = WooFixtures::simple_product( array( 'name' => 'Find me', 'sku' => 'FIND-1', 'slug' => 'find-me' ) );
		$id         = $wc_product->get_id();

		$by_id = $this->products()->get( $id );
		$this->assertInstanceOf( Product::class, $by_id );
		$this->assertSame( $id, $by_id->id );
		$this->assertSame( 'Find me', $by_id->name );

		$by_object = $this->products()->get( wc_get_product( $id ) );
		$this->assertSame( $id, $by_object->id );

		$by_sku = $this->products()->get( 'FIND-1' );
		$this->assertSame( $id, $by_sku->id );

		$by_slug = $this->products()->get( 'find-me' );
		$this->assertSame( $id, $by_slug->id );

		$this->assertSame( $by_id, $this->products()->get( $by_id ) );

		$this->assertNull( $this->products()->get( 999999 ) );
	}

	public function test_accessor_props_read_and_write_through_wc_product(): void {
		$product = $this->products()->get( WooFixtures::simple_product( array( 'regular_price' => '25' ) )->get_id() );

		$this->assertSame( 25.0, $product->price );
		$this->assertSame( 'simple', $product->type );

		$product->name = 'Renamed';
		$this->assertSame( 'Renamed', $product->get_wc_product()->get_name(), 'AccessorObject::set writes through to the source' );
	}

	public function test_save_updates_existing_product(): void {
		$product = $this->products()->get( WooFixtures::simple_product()->get_id() );

		$product->name = 'Saved name';
		$this->products()->save( $product );

		$this->assertSame( 'Saved name', wc_get_product( $product->id )->get_name() );
	}

	public function test_save_inserts_product_with_attached_source(): void {
		$product = $this->products()->create();
		$product->source( new \WC_Product_Simple() );
		$product->name = 'Created product';

		$saved = $this->products()->save( $product );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'Created product', wc_get_product( $saved->id )->get_name() );
	}

	/**
	 * create() with data no longer fatals for AccessorObject models without a source.
	 *
	 * @see https://github.com/wpify/model/issues/39
	 */
	public function test_create_with_data(): void {
		$product = $this->products()->create( array( 'name' => 'Created' ) );

		$this->assertInstanceOf( Product::class, $product );
		$this->assertSame( 'Created', $product->name );

		// The accessor write on create() had no source to reach, so attach one
		// and set the name again before persisting.
		$product->source( new \WC_Product_Simple() );
		$product->name = 'Created';

		$saved = $this->products()->save( $product );

		$this->assertGreaterThan( 0, $saved->id );
		$this->assertSame( 'Created', wc_get_product( $saved->id )->get_name() );
	}

	public function test_delete_removes_product(): void {
		$product = $this->products()->get( WooFixtures::simple_product()->get_id() );

		$this->assertTrue( $this->products()->delete( $product ) );
		$this->assertNull( $this->products()->get( $product->id ) );
	}

	public function test_find_and_find_all(): void {
		WooFixtures::simple_product( array( 'name' => 'A' ) );
		WooFixtures::simple_product( array( 'name' => 'B' ) );

		$found = $this->products()->find( array( 'limit' => -1 ) );
		$this->assertCount( 2, $found );

		$this->assertCount( 2, $this->products()->find_all() );

		$ids = $this->products()->find( array( 'return' => 'ids', 'limit' => -1 ) );
		$this->assertIsInt( $ids[0], 'return=ids bypasses model hydration' );
	}

	public function test_find_by_ids(): void {
		$first = WooFixtures::simple_product()->get_id();
		WooFixtures::simple_product();
		$third = WooFixtures::simple_product()->get_id();

		$found = $this->products()->find_by_ids( array( $first, $third ) );

		$this->assertCount( 2, $found );
	}

	public function test_find_all_by_term_and_tax_query_filter(): void {
		$term_id = WooFixtures::product_cat( 'Shirts' );
		$in_cat  = WooFixtures::simple_product( array( 'name' => 'In cat' ) );
		WooFixtures::simple_product( array( 'name' => 'Out of cat' ) );

		wp_set_post_terms( $in_cat->get_id(), array( $term_id ), 'product_cat' );

		$category = $this->repo( ProductCatRepository::class )->get( $term_id );
		$found    = $this->products()->find_all_by_term( $category );

		$this->assertCount( 1, $found );
		$this->assertSame( $in_cat->get_id(), $found[0]->id );
	}

	public function test_find_all_by_term_throws_for_repository_without_taxonomy(): void {
		$product = $this->products()->get( WooFixtures::simple_product()->get_id() );

		$this->expectException( IncorrectRepositoryException::class );

		$this->products()->find_all_by_term( $product );
	}

	public function test_tax_query_filter_appends_tax_query(): void {
		$query = $this->products()->tax_query_filter(
			array( 'tax_query' => array( array( 'existing' => true ) ) ),
			array( 'tax_query' => array( array( 'taxonomy' => 'product_cat' ) ) )
		);

		$this->assertCount( 2, $query['tax_query'] );

		$untouched = $this->products()->tax_query_filter( array( 'tax_query' => array() ), array() );
		$this->assertSame( array( 'tax_query' => array() ), $untouched );
	}

	public function test_product_categories_relation(): void {
		$term_id = WooFixtures::product_cat( 'Related cat' );
		$product = WooFixtures::simple_product();

		wp_set_post_terms( $product->get_id(), array( $term_id ), 'product_cat' );

		$model = $this->products()->get( $product->get_id() );

		$this->assertCount( 1, $model->categories );
		$this->assertInstanceOf( ProductCat::class, $model->categories[0] );
		$this->assertSame( $term_id, $model->categories[0]->id );
	}

	public function test_get_wc_product_returns_source(): void {
		$product = $this->products()->get( WooFixtures::simple_product()->get_id() );

		$this->assertInstanceOf( \WC_Product::class, $product->get_wc_product() );
		$this->assertSame( $product->id, $product->get_wc_product()->get_id() );
	}

	public function test_get_vat_rate_uses_matching_tax_rate(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'CZ',
				'tax_rate'          => '21.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);

		$product = $this->products()->get( WooFixtures::simple_product( array( 'regular_price' => '100' ) )->get_id() );

		$this->assertSame( 21.0, (float) $product->get_vat_rate( 'CZ' ) );
	}

	public function test_product_cat_repository(): void {
		$repository = $this->repo( ProductCatRepository::class );

		$this->assertSame( ProductCat::class, $repository->model() );
		$this->assertSame( 'product_cat', $repository->taxonomy() );

		$term_id = WooFixtures::product_cat( 'Repo cat' );
		$this->assertInstanceOf( ProductCat::class, $repository->get( $term_id ) );
	}
}
