<?php
declare( strict_types=1 );

namespace Wpify\Model;

use WP_Site;
use Wpify\Model\Attributes\Meta;
use Wpify\Model\Attributes\SourceObject;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

class SiteRepository extends Repository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Site::class;
	}

	/**
	 * Returns the Site model by the WP_Site object or id.
	 *
	 * @param mixed $source
	 *
	 * @return ModelInterface|null
	 * @throws Exceptions\RepositoryNotInitialized
	 */
	public function get( mixed $source ): ?ModelInterface {
		$wp_site = null;
		$site    = null;
		$model   = $this->model();

		if ( $source instanceof $model ) {
			return $source;
		}

		if ( $source instanceof WP_Site ) {
			$wp_site = $source;
		}

		if ( ! $wp_site ) {
			$wp_site = get_site( $source );
		}

		if ( $wp_site ) {
			$model_class = $this->model();
			$site        = new $model_class( $this->manager() );

			$site->source( $wp_site );
		}

		return $site;
	}

	/**
	 * Saves the site to the database.
	 *
	 * @param ModelInterface $model
	 *
	 * @return ModelInterface
	 * @throws CouldNotSaveModelException
	 */
	public function save( ModelInterface $model ): ModelInterface {
		$data = array();
		$meta = array();

		foreach ( $model->props() as $prop ) {
			if ( $prop['readonly'] ) {
				continue;
			}

			$source = $prop['source'] ?? null;

			if ( method_exists( $model, 'persist_' . $prop['name'] ) ) {
				$model->{'persist_' . $prop['name']}( $model->{$prop['name']} );
			} elseif ( $source instanceof SourceObject ) {
				$key          = $source->key ?? $prop['name'];
				$data[ $key ] = $model->{$prop['name']};
			} elseif ( $source instanceof Meta ) {
				$key          = $source->meta_key ?? $prop['name'];
				$meta[ $key ] = $model->{$prop['name']};
			}
		}

		// wp_insert_site()/wp_update_site() use the network id keyed as network_id
		// and take the site id as a separate argument rather than in $data.
		unset( $data['blog_id'] );

		if ( isset( $data['site_id'] ) ) {
			$data['network_id'] = $data['site_id'];
			unset( $data['site_id'] );
		}

		if ( $model->id > 0 ) {
			$result = wp_update_site( $model->id, $data );
			$action = 'update';
		} else {
			$result = wp_insert_site( $data );
			$action = 'insert';
		}

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message(), 0, $result );
		}

		$site_id = (int) $result;

		if ( function_exists( 'is_site_meta_supported' ) && is_site_meta_supported() ) {
			foreach ( $meta as $meta_key => $value ) {
				update_site_meta( $site_id, $meta_key, $value );
			}
		}

		if ( apply_filters( 'wpify_model_refresh_model_after_save', true, $model, $this ) ) {
			$model->refresh( get_site( $site_id ) );
		}

		do_action( 'wpify_model_repository_save_' . $action, $model, $this );

		return $model;
	}

	/**
	 * Deletes the site from the database.
	 *
	 * @param ModelInterface $model
	 * @param bool           $force_delete Unused.
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model, bool $force_delete = true ): bool {
		return ! is_wp_error( wp_delete_site( $model->id ) );
	}

	/**
	 * Returns a list of sites.
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_sites/ for more information.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws Exceptions\RepositoryNotInitialized
	 */
	public function find( array $args = array() ): array {
		$sites  = get_sites( $args );
		$result = array();

		foreach ( $sites as $site ) {
			$result[] = $this->get( $site );
		}

		return $result;
	}

	/**
	 * Returns all sites.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws RepositoryNotInitialized
	 */
	public function find_all( array $args = array() ): array {
		return $this->find( $args );
	}

	/**
	 * Returns a list of sites by ids.
	 *
	 * @param array $ids
	 *
	 * @return array
	 * @throws RepositoryNotInitialized
	 */
	public function find_by_ids( array $ids ): array {
		return $this->find( array( 'site__in' => $ids ) );
	}
}
