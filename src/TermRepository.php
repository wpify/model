<?php

declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\Meta;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

/**
 * Repository for Term models.
 * @method Term create( array $data )
 */
class TermRepository extends Repository {
	/**
	 * Returns the model class name.
	 * @return string
	 */
	public function model(): string {
		return Term::class;
	}

	/**
	 * Returns taxonomy for the repository.
	 * @return string
	 */
	public function taxonomy(): string {
		return '';
	}

	/**
	 * Returns the Term model by the WP_Term object, id, slug or name.
	 *
	 * @param mixed $source
	 *
	 * @return ?Term
	 * @throws RepositoryNotInitialized
	 */
	public function get( mixed $source ): ?ModelInterface {
		$wp_term = null;
		$term    = null;
		$model   = $this->model();

		if ( $source instanceof $model ) {
			return $source;
		}

		if ( $source instanceof \WP_Term ) {
			$wp_term = $source;
		}

		if ( ! $wp_term && is_numeric( $source ) ) {
			$wp_term = get_term( $source, $this->taxonomy() );
		}

		if ( ! $wp_term && is_string( $source ) ) {
			$wp_term = get_term_by( 'slug', $source, $this->taxonomy() );
		}

		if ( ! $wp_term && is_string( $source ) ) {
			$wp_term = get_term_by( 'name', $source, $this->taxonomy() );
		}

		if ( $wp_term ) {
			$model_class = $this->model();
			$term        = new $model_class( $this->manager() );

			$term->source( $wp_term );
		}

		return $term;
	}

	/**
	 * Saves the term model.
	 *
	 * @param Term $model
	 *
	 * @return Term
	 * @throws CouldNotSaveModelException
	 */
	public function save( ModelInterface $model ): ModelInterface {
		$data     = array();
		$taxonomy = $model->taxonomy ?? $this->taxonomy();

		if ( $model->id > 0 ) {
			$result = wp_update_term( $model->id, $taxonomy, array(
				'name'        => $model->name,
				'slug'        => $model->slug,
				'term_group'  => $model->group,
				'description' => $model->description,
				'parent'      => $model->parent_id,
				'alias_of'    => $model->alias_of,
			) );
			$action = 'update';
		} else {
			$result = wp_insert_term( $model->name, $taxonomy, array(
				'slug'        => $model->slug,
				'term_group'  => $model->group,
				'description' => $model->description,
				'parent'      => $model->parent_id,
				'alias_of'    => $model->alias_of,
			) );
			$action = 'insert';
		}

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message(), 0, $result );
		}

		$term_id = is_array( $result ) ? $result['term_id'] : $result;

		foreach ( $model->props() as $prop ) {
			if ( $prop['readonly'] ) {
				continue;
			}

			$source = $prop['source'];

			if ( method_exists( $model, 'persist_' . $prop['name'] ) ) {
				$model->{'persist_' . $prop['name']}( $model->{$prop['name']} );
			} elseif ( $source instanceof Meta ) {
				$meta_key = $source->key ?? $prop['name'];

				update_term_meta( $term_id, $meta_key, $model->{$prop['name']} );
			}
		}

		if ( apply_filters( 'wpify_model_refresh_model_after_save', true, $model, $this ) ) {
			$model->refresh( get_term( $term_id, $taxonomy ) );
		}

		do_action( 'wpify_model_repository_save_' . $action, $model, $this );

		return $model;
	}

	/**
	 * Deletes the given term.
	 *
	 * @param Term $model
	 * @param bool $force_delete Unused.
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model, bool $force_delete = true ): bool {
		return ! is_wp_error( wp_delete_term( $model->id, $model->taxonomy ?? $this->taxonomy() ) );
	}

	/**
	 * Finds posts matching the given arguments.
	 *
	 * @param array $args
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find( array $args = [] ): array {
		$defaults   = array( 'taxonomy' => $this->taxonomy() );
		$args       = wp_parse_args( $args, $defaults );
		$collection = array();

		if ( empty( $args['taxonomy'] ) ) {
			unset( $args['taxonomy'] );
		}

		$terms = get_terms( $args );

		foreach ( $terms as $term ) {
			$collection[] = $this->get( $term );
		}

		return $collection;
	}

	/**
	 * Finds all terms whenever they are empty or not.
	 *
	 * @param array $args
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_all( array $args = array() ): array {
		$args = array_merge( array( 'hide_empty' => false ), $args );

		return $this->find( $args );
	}

	/**
	 * Finds all terms that are not empty.
	 *
	 * @param array $args
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_not_empty( array $args = array() ): array {
		$args = array_merge( array( 'hide_empty' => true ), $args );

		return $this->find( $args );
	}

	/**
	 * Find all terms that are parent of the given term.
	 *
	 * @param int   $parent_id
	 * @param array $args
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_children_of( int $parent_id = 0, array $args = array() ): array {
		if ( $parent_id > 0 ) {
			$args = array_merge( array( 'child_of' => $parent_id ), $args );

			return $this->find( $args );
		}

		return array();
	}

	/**
	 * Finds all terms that are assigned to the given post.
	 *
	 * @param int $post_id
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_terms_of_post( int $post_id ): array {
		$collection = array();
		$terms      = get_the_terms( $post_id, $this->taxonomy() );

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$collection[] = $this->get( $term );
			}
		}

		return $collection;
	}

	/**
	 * Finds all terms that are assigned as a children.
	 *
	 * @param ModelInterface $model
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_child_terms_of( ModelInterface $model ): array {
		return $this->find( array(
			                    'taxonomy'     => $model->taxonomy ?? $this->taxonomy(),
			                    'hierarchical' => true,
			                    'hide_empty'   => false,
			                    'child_of'     => $model->id,
		                    ) );
	}

	/**
	 * Find all terms with the given ids.
	 *
	 * @param array $ids
	 * @param array $args
	 *
	 * @return Term[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_by_ids( array $ids, array $args = array() ): array {
		return $this->find( array_merge( $args, array(
			'include' => $ids,
		) ) );
	}
}
