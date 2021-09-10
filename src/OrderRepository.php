<?php

namespace Wpify\Model;

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
 */
class OrderRepository extends AbstractRepository implements RepositoryInterface {
	private $item_repository;

	static function post_type(): string {
		return 'shop_order';
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
		$items    = wc_get_orders( $args );

		$collection = array();

		foreach ( $items as $item ) {
			$collection[] = $this->factory( $item );
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
		$order       = $model->source_object();
		foreach ( $model->own_props() as $key => $prop ) {
			if ( ! empty( $prop['readonly'] ) ) {
				continue;
			}
			// TODO: uncomment this if needed to set object data back
//			$source_name = $prop['source_name'];
//            if ($prop['source'] === 'object') {
//                $object_data[$source_name] = $model->{$key};
//            }
//
			if ( $prop['source'] === 'meta' ) {
				$order->update_meta_data( $key, $model->{$key} );
			} elseif ( $prop['source'] === 'relation' && !empty($prop['assign']) && \is_callable( $prop['assign'] ) && $prop['changed'] ) {
				$prop['assign']( $model );
			}
		}

		$result = $order->save();
		if ( $result && ! is_wp_error( $result ) ) {
			$model->refresh( $this->resolve_object( $result ) );
		} else {
			throw new NotPersistedException();
		}

		return $model;
	}

	/**
	 * @param $data
	 *
	 * @return \WC_Order
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data ): \WC_Order {
		if ( is_object( $data ) && get_class( $data ) === $this->model() ) {
			$object = $data->source_object();
		} elseif ( $data instanceof \WC_Order ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WC_Order();
		} elseif ( is_numeric( $data ) ) {
			$object = wc_get_order( $data );
		} elseif ( isset( $data->id ) ) {
			$object = wc_get_order( $data->id );
		} else {
			$object = wc_get_order( $data );
		}

		if ( ! ( $object instanceof \WC_Order ) ) {
			throw new NotFoundException( 'The order was not found' );
		}

		return $object;
	}

	public function model(): string {
		return Order::class;
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
	 * @param PostModelInterface   $model
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

	public function get_item_repository( $model = OrderItemLine::class ) {
		// TODO: Cache this
		return new OrderItemRepository( $model );
	}

	public function count( $args = [] ) {
		$args  = wp_parse_args(
			$args,
			[
				'limit'  => - 1,
				'return' => 'ids',
			]
		);
		$items = wc_get_orders( $args );

		return count( $items );
	}

}
