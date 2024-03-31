<?php

declare( strict_types=1 );

namespace Wpify\Model;

use WC_Product;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\IncorrectRepositoryException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

/**
 * Repository for Post models.
 *
 * @method Product create( array $data = array() )
 */
class ProductRepository extends Repository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Product::class;
	}

	/**
	 * Returns the Post model by the WP_Post object, id, slug or URL.
	 *
	 * @param mixed $source
	 *
	 * @return ?Post
	 * @throws RepositoryNotInitialized
	 */
	public function get( mixed $source ): ?ModelInterface {
		$wc_product = null;
		$product    = null;

		if ( $source instanceof WC_Product ) {
			$wc_product = $source;
		}

		if ( ! $wc_product && is_numeric( $source ) ) {
			$wc_product = wc_get_product( $source );
		}

		if ( ! $wc_product ) {
			$wc_products = wc_get_products( array(
				'sku'            => $source,
				'posts_per_page' => 1,
			) );

			if ( ! is_wp_error( $wc_products ) && count( $wc_products ) > 0 ) {
				$wc_product = $wc_products[0];
			}
		}

		if ( ! $wc_product && is_string( $source ) ) {
			$wc_product = get_page_by_path( $source, OBJECT, 'product' );

			if ( $wc_product && ! is_wp_error( $wc_product ) ) {
				$wc_product = wc_get_product( $wc_product->ID );
			}
		}

		if ( $wc_product ) {
			$model_class = $this->model();
			$product     = new $model_class( $this->manager() );

			$product->source( $wc_product );
		}

		return $product;
	}

	/**
	 * Stores post into database.
	 *
	 * @param Post $model
	 *
	 * @return Post
	 * @throws CouldNotSaveModelException
	 */
	public function save( ModelInterface $model ): ModelInterface {
		foreach ( $model->props() as $prop ) {
			if ( $prop['readonly'] ) {
				continue;
			}

			if ( method_exists( $model, 'persist_' . $prop['name'] ) ) {
				$model->{'persist_' . $prop['name']}( $model->{$prop['name']} );
			}
		}

		$action = $model->source()->get_id() ? 'update' : 'insert';
		$result = $model->source()->save();

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message() );
		}

		if ( apply_filters( 'wpify_model_refresh_model_after_save', true, $model, $this ) ) {
			$model->refresh( wc_get_product( $result ) );
		}

		do_action( 'wpify_model_repository_save_' . $action, $model, $this );

		return $model;
	}

	/**
	 * Deletes the given product.
	 *
	 * @param Post $model
	 * @param bool $force_delete
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model, bool $force_delete = true ): bool {
		return boolval( $model->source()->delete( $force_delete ) );
	}

	/**
	 * Finds products matching the given arguments.
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 *
	 * @param array $args
	 *
	 * @return Post[]
	 * @throws RepositoryNotInitialized
	 */
	public function find( array $args = array() ): array {
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'tax_query_filter' ), 10, 2 );

		$items = wc_get_products( $args );

		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'tax_query_filter' ), 10, 2 );

		$collection = array();

		foreach ( $items as $item ) {
			$collection[] = $this->get( $item );
		}

		return $collection;
	}

	/**
	 * Filters the query to include the tax query.
	 *
	 * @param $query
	 * @param $query_vars
	 *
	 * @return mixed
	 */
	public function tax_query_filter( $query, $query_vars ) {
		if ( ! empty( $query_vars['tax_query'] ) ) {
			$query['tax_query'][] = $query_vars['tax_query'];
		}

		return $query;
	}

	/**
	 * Finds all posts.
	 *
	 * @param array $args
	 *
	 * @return Post[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_all( array $args = array() ): array {
		$defaults = array(
			'limit' => - 1,
		);

		$args = wp_parse_args( $args, $defaults );

		return $this->find( $args );
	}

	/**
	 * Find all posts with the given ids.
	 *
	 * @param array $ids
	 * @param array $args
	 *
	 * @return Post[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_by_ids( array $ids, array $args = array() ): array {
		$defaults = array(
			'limit'   => - 1,
			'include' => $ids,
		);

		$args = wp_parse_args( $args, $defaults );

		return $this->find( $args );
	}

	/**
	 * Find all posts by the given term.
	 *
	 * @param Term $model
	 *
	 * @return Product[]
	 * @throws Exceptions\RepositoryNotFoundException
	 * @throws IncorrectRepositoryException
	 * @throws RepositoryNotInitialized
	 */
	public function find_all_by_term( ModelInterface $model ): array {
		$target_repository = $this->manager()->get_model_repository( get_class( $model ) );

		if ( method_exists( $target_repository, 'taxonomy' ) ) {
			return $this->find( array(
				'tax_query' => array(
					array(
						'taxonomy' => $target_repository->taxonomy(),
						'field'    => 'term_id',
						'terms'    => array( $model->id ),
					),
				),
			) );
		}

		throw new IncorrectRepositoryException( sprintf( 'The repository %s of model %s does not have a taxonomy method.', get_class( $target_repository ), get_class( $model ) ) );
	}
}
