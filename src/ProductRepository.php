<?php

namespace Wpify\Model;

use WC_Product;
use Wpify\Model\Abstracts\AbstractPostModel;
use Wpify\Model\Abstracts\AbstractRepository;
use Wpify\Model\Exceptions\NotFoundException;
use Wpify\Model\Exceptions\NotPersistedException;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\PostModelInterface;
use Wpify\Model\Interfaces\RepositoryInterface;
use Wpify\Model\Interfaces\TermModelInterface;

/**
 * Class BasePostRepository
 * @package Wpify\Model
 *
 */
class ProductRepository extends AbstractRepository implements RepositoryInterface {
	static function post_type(): string {
		return 'product';
	}

	public function fetch_parent( AbstractPostModel $model ) {
		return $this->get( $model->parent_id );
	}

	/**
	 * @param ?object $object
	 */
	public function get( $object = null ) {
		return ! empty( $object ) ? $this->factory( $object ) : null;
	}

	/**
	 * @return AbstractPostModel[]
	 */
	public function all() {
		$args = array( 'limit' => - 1 );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() ) {
		$defaults = [];
		$args     = wp_parse_args( $args, $defaults );
		$products = wc_get_products( $args );

		$collection = array();

		foreach ( $products as $product ) {
			$collection[] = $this->factory( $product );
		}

		return $this->collection_factory( $collection );
	}

	/**
	 * @return AbstractPostModel
	 */
	public function create() {
		return $this->factory( null );
	}

	/**
	 * @param ModelInterface $model
	 *
	 * @return ModelInterface
	 * @throws NotFoundException
	 * @throws NotPersistedException
	 */
	public function save( $model ) {
		$object_data = array();

		foreach ( $model->own_props() as $key => $prop ) {
			$source_name = $prop['source_name'];

			if ( $prop['source'] === 'object' ) {
				$object_data[ $source_name ] = $model->$key;
			} elseif ( $prop['source'] === 'meta' ) {
				if ( ! isset( $object_data['meta_input'] ) ) {
					$object_data['meta_input'] = array();
				}

				$object_data['meta_input'][ $source_name ] = $model->$key;
			} elseif ( $prop['source'] === 'relation' && is_callable( $prop['assign'] ) && $prop['changed'] ) {
				$prop['assign']( $model );
			}
		}

		if ( $model->id > 0 ) {
			$result = wp_update_post( $object_data, true );
		} else {
			$result = wp_insert_post( $object_data, true );
		}

		if ( ! is_wp_error( $result ) ) {
			$model->refresh( $this->resolve_object( $result ) );
		} else {
			throw new NotPersistedException();
		}

		return $model;
	}

	/**
	 * @param $data
	 *
	 * @return WC_Product
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data ): WC_Product {
		if ( is_object( $data ) && get_class( $data ) === $this::model() ) {
			$object = $data->source_object();
		} elseif ( $data instanceof WC_Product ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WC_Product();
		} elseif ( isset( $data->id ) ) {
			$object = wc_get_product( $data->id );
		} else {
			$object = wc_get_product( $data );
		}

		if ( ! ( $object instanceof WC_Product ) ) {
			throw new NotFoundException( 'The product was not found' );
		}

		return $object;
	}

	public function model(): string {
		return Product::class;
	}

	/**
	 * @param PostModelInterface $model
	 *
	 * @return mixed
	 */
	public function delete( PostModelInterface $model ) {
		return wp_delete_post( $model->id, true );
	}

	/**
	 * Assign the post to the terms
	 *
	 * @param PostModelInterface $model
	 * @param TermModelInterface[] $terms
	 */
	public function assign_post_to_term( PostModelInterface $model, array $terms ) {
		$to_assign = [];

		foreach ( $terms as $term ) {
			if ( isset( $to_assign[ $term->taxonomy_name ] ) && is_array( $to_assign[ $term->taxonomy_name ] ) ) {
				$to_assign[ $term->taxonomy_name ][] = $term;
			} else {
				$to_assign[ $term->taxonomy_name ] = array( $term );
			}
		}

		foreach ( $to_assign as $taxonomy => $assigns ) {
			wp_set_post_terms( $model->id, array_values( array_map( function ( $term ) {
				return $term->id;
			}, $assigns ) ), $taxonomy );
		}
	}
}
