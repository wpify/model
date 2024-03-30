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

		foreach ( $model->props() as $prop ) {
			if ( empty( $prop['source'] ) || $prop['readonly'] ) {
				continue;
			}

			$source = $prop['source'];

			if ( method_exists( $model, 'persist_' . $prop['name'] ) ) {
				$model->{'persist_' . $prop['name']}( $model->{$prop['name']} );
			} elseif ( $source instanceof SourceObject ) {
				$key          = $source->key ?? $prop['name'];
				$data[ $key ] = $model->{$prop['name']};
			} elseif ( $source instanceof Meta ) {
				$key                  = $source->key ?? $prop['name'];
				$data['meta'][ $key ] = $model->{$prop['name']};
			}
		}

		if ( $data['blog_id'] > 0 ) {
			$result = wp_update_comment( $data, true );
		} else {
			$result = wp_insert_comment( $data );
		}

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message() );
		}

		$model->refresh( get_user_by( 'id', $result ) );

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
