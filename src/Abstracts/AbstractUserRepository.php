<?php

namespace Wpify\Model\Abstracts;

use WP_User;
use WP_User_Query;
use Wpify\Model\Exceptions\NotFoundException;
use Wpify\Model\Exceptions\NotPersistedException;
use Wpify\Model\Interfaces\UserRepositoryInterface;

abstract class AbstractUserRepository extends AbstractRepository implements UserRepositoryInterface {
	private $query;

	/**
	 * @return AbstractPostModel[]
	 */
	public function all() {
		$args = array( 'number' => - 1 );

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() ) {
		$this->query = new WP_User_Query( $args );
		$collection  = array();

		if ( ! empty( $this->query->get_results() ) ) {
			foreach ( $this->query->get_results() as $user ) {
				$collection[] = $this->factory( $user );
			}
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
	 * @param AbstractUserModel $model
	 *
	 * @return mixed
	 * @throws NotPersistedException
	 * @throws NotFoundException
	 */
	public function save( $model ) {
		$object_data = array();

		foreach ( $model->own_props() as $key => $prop ) {
			$source_name = $prop['source_name'];

			if ( $prop['source'] === 'object' ) {
				$object_data[ $source_name ] = $model->$key;
			}
		}

		if ( $model->id > 0 ) {
			$result = wp_update_user( $object_data );
		} else {
			$result = wp_insert_user( $object_data );

			if ( ! is_wp_error( $result ) ) {
				$model->id = $result;
			}
		}

		if ( is_wp_error( $result ) ) {
			throw new NotPersistedException( $result->get_error_message() );
		}

		if ( $model->id ) {
			foreach ( $model->own_props() as $key => $prop ) {
				if ( $prop['source'] === 'meta' && $prop['changed'] ) {
					$model->store_meta( $prop['source_name'], $model->$key );
				} elseif ( $prop['source'] === 'relation' && is_callable( $prop['assign'] ) && $prop['changed'] ) {
					$prop['assign']( $model );
				}
			}
		}

		if ( ! is_wp_error( $result ) ) {
			$model->refresh( $this->resolve_object( $result ) );
		}

		return $model;
	}

	/**
	 * @param $data
	 *
	 * @return WP_User
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data ): WP_User {
		if ( is_object( $data ) && get_class( $data ) === $this->model() ) {
			$object = $data->source_object();
		} elseif ( $data instanceof WP_User ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WP_User( (object) array(
				'ID'                  => null,
				'user_login'          => '',
				'user_pass'           => '',
				'user_nicename'       => '',
				'user_email'          => '',
				'user_url'            => '',
				'user_registered'     => '',
				'user_activation_key' => '',
				'user_status'         => 1,
				'display_name'        => '',
			) );
		} elseif ( isset( $data->id ) ) {
			$object = get_user_by( 'ID', $data->id );
		} elseif ( is_numeric( $data ) ) {
			$object = get_user_by( 'ID', $data );
		} elseif ( is_email( $data ) ) {
			$object = get_user_by( 'email', $data );
		}

		if ( ! ( $object instanceof WP_User ) ) {
			throw new NotFoundException( 'The user was not found' );
		}

		return $object;
	}

	/**
	 * @param AbstractUserModel $model
	 *
	 * @return mixed
	 */
	public function delete( $model ) {
		return wp_delete_user( $model->id, true );
	}

	public function get_current_user() {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		return $this->get( get_current_user_id() );
	}

	/**
	 * @param ?object $object
	 */
	public function get( $object = null ) {
		return ! empty( $object ) ? $this->factory( $object ) : null;
	}
}
