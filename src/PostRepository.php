<?php

declare( strict_types=1 );

namespace Wpify\Model;

use WP_Post;
use WP_Query;
use Wpify\Model\Attributes\Meta;
use Wpify\Model\Attributes\SourceObject;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\IncorrectRepositoryException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

/**
 * Repository for Post models.
 */
class PostRepository extends Repository {
	private ?WP_Query $query;

	/**
	 * Returns the model class name.
	 * @return string
	 */
	public function model(): string {
		return Post::class;
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
		$wp_post = null;
		$post    = null;

		if ( $source instanceof WP_Post ) {
			$wp_post = $source;
		}

		if ( ! $wp_post && is_numeric( $source ) ) {
			$wp_post = get_post( $source );
		}

		if ( ! $wp_post && is_string( $source ) ) {
			$wp_posts = get_posts( array(
				'name'           => $source,
				'post_type'      => $this->post_types(),
				'posts_per_page' => 1,
			) );

			if ( ! is_wp_error( $wp_posts ) && count( $wp_posts ) > 0 ) {
				$wp_post = $wp_posts[0];
			}
		}

		if ( ! $wp_post && is_string( $source ) ) {
			$wp_post_id = url_to_postid( $source );

			if ( $wp_post_id > 0 ) {
				$wp_post = get_post( $wp_post_id );
			}
		}

		if ( $wp_post ) {
			$model_class = $this->model();
			$post        = new $model_class( $this->manager() );

			$post->source( $wp_post );
		}

		return $post;
	}

	/**
	 * Returns post types for the repository.
	 * @return string[]
	 */
	public function post_types(): array {
		return array( 'post' );
	}

	/**
	 * Creates a new post model.
	 * If the repository has multiple post types or no post types, this will throw an exception.
	 *
	 * @param array $data Data to set on the model.
	 *
	 * @return Post
	 * @throws CouldNotSaveModelException
	 * @throws RepositoryNotInitialized
	 */
	public function create( array $data = array() ): ModelInterface {
		if ( count( $this->post_types() ) > 1 ) {
			throw new CouldNotSaveModelException( 'Cannot create an item with multiple post types.' );
		}

		if ( count( $this->post_types() ) < 1 ) {
			throw new CouldNotSaveModelException( 'Cannot create an item with no post types.' );
		}

		/** @var Post $model */
		$model            = parent::create( $data );
		$post_type        = $this->post_types()[0];
		$model->post_type = $post_type;

		return $model;
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
		$data = array();

		foreach ( $model->props() as $prop ) {
			if ( empty( $prop['source'] ) || $prop['readonly'] ) {
				continue;
			}

			$source = $prop['source'];
			$key    = $source->key ?? $prop['name'];

			if ( method_exists( $model, 'persist_' . $prop['name'] ) ) {
				$model->{'persist_' . $prop['name']}( $model->{$prop['name']} );
			} elseif ( $source instanceof SourceObject ) {
				$data[ $key ] = $model->{$prop['name']};
			} elseif ( $source instanceof Meta ) {
				$data['meta_input'][ $key ] = $model->{$prop['name']};
			}
		}

		if ( $data['ID'] > 0 ) {
			$result = wp_update_post( $data, true );
			$action = 'update';
		} else {
			$result = wp_insert_post( $data, true );
			$action = 'insert';
		}

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message() );
		}

		foreach ( $model->props() as $prop ) {
			if ( empty( $prop['source'] ) || $prop['readonly'] ) {
				continue;
			}

			$source = $prop['source'];
			$key    = $source->key ?? $prop['name'];

			if ( method_exists( $source, 'persist' ) ) {
				$source->persist( $model, $key, $model->{$prop['name']} );
			}
		}

		if ( apply_filters( 'wpify_model_refresh_model_after_save', true, $model, $this ) ) {
			$model->refresh( get_post( $result ) );
		}

		do_action( 'wpify_model_repository_save_' . $action, $model, $this );

		return $model;
	}

	/**
	 * Deletes the given post.
	 *
	 * @param Post $model
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model ): bool {
		return boolval( wp_delete_post( $model->id, true ) );
	}

	/**
	 * Retrieves paginated links for archive post pages.
	 * @see https://developer.wordpress.org/reference/functions/paginate_links/
	 *
	 * @param array $args
	 *
	 * @return string|string[]|null
	 */
	public function get_paginate_links( array $args = array() ): array|string|null {
		$pagination   = $this->get_pagination();
		$default_args = array(
			'total'   => $pagination['total_pages'],
			'current' => $pagination['current_page'],
		);

		return paginate_links( wp_parse_args( $args, $default_args ) );
	}

	/**
	 * Retrieves information about pagination from the last query.
	 * @return array
	 */
	public function get_pagination(): array {
		return array(
			'found_posts'  => $this->query->found_posts,
			'current_page' => $this->query->query_vars['paged'] ?: 1,
			'total_pages'  => $this->query->max_num_pages,
			'per_page'     => $this->query->query_vars['posts_per_page'],
		);
	}

	/**
	 * Finds posts matching the given arguments.
	 * @see https://developer.wordpress.org/reference/classes/wp_query/
	 *
	 * @param array $args
	 *
	 * @return Post[]
	 * @throws RepositoryNotInitialized
	 */
	public function find( array $args = array() ): array {
		$defaults = array(
			'post_type'      => $this->post_types(),
			'post_status'    => 'any',
			'posts_per_page' => - 1,
		);

		$args        = wp_parse_args( $args, $defaults );
		$this->query = new WP_Query( $args );
		$collection  = array();

		while ( $this->query->have_posts() ) {
			$this->query->the_post();

			global $post;

			$collection[] = $this->get( $post );
		}

		wp_reset_postdata();

		return $collection;
	}

	/**
	 * Find paginated posts matching the given arguments.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws RepositoryNotInitialized
	 */
	public function find_paginated( array $args = array() ): array {
		$defaults = array(
			'paged'          => get_query_var( 'paged' ) ?: 1,
			'post_type'      => $this->post_types(),
			'post_status'    => 'any',
			'posts_per_page' => - 1,
		);

		$args = wp_parse_args( $args, $defaults );

		$this->query    = new WP_Query( $args );
		$pagination     = $this->get_pagination();
		$paginate_links = $this->get_paginate_links();
		$collection     = array();

		while ( $this->query->have_posts() ) {
			$this->query->the_post();

			global $post;

			$collection[] = $this->get( $post );
		}

		wp_reset_postdata();

		return [
			'pagination'    => $pagination,
			'paginate_link' => $paginate_links,
			'items'         => $collection
		];
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
			'posts_per_page' => - 1,
			'post_status'    => 'any',
		);

		$args = wp_parse_args( $args, $defaults );

		return $this->find( $args );
	}

	/**
	 * Finds all published posts.
	 *
	 * @param array $args
	 *
	 * @return Post[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_published( array $args = array() ): array {
		$defaults = array(
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		);

		$args = wp_parse_args( $args, $defaults );

		return $this->find( $args );
	}

	/**
	 * Find all posts by the given term.
	 *
	 * @param ModelInterface $model
	 *
	 * @return Post[]
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

	/**
	 * Finds all posts that have the given post as a parent.
	 *
	 * @param ModelInterface $model
	 *
	 * @return Post[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_child_posts_of( ModelInterface $model ): array {
		return $this->find( array(
			'post_parent' => $model->id,
		) );
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
		return $this->find( array_merge( $args, array(
			'post__in' => $ids,
		) ) );
	}

	/**
	 * Assign the post to the terms
	 *
	 * @param ModelInterface $model
	 * @param Term[] $terms
	 * @param bool $append
	 */
	public function assign_post_to_term( ModelInterface $model, array $terms, bool $append = false ): void {
		$to_assign = array();

		foreach ( $terms as $term ) {
			if ( isset( $to_assign[ $term->taxonomy ] ) && is_array( $to_assign[ $term->taxonomy ] ) ) {
				$to_assign[ $term->taxonomy ][] = $term;
			} else {
				$to_assign[ $term->taxonomy ] = array( $term );
			}
		}

		foreach ( $to_assign as $taxonomy => $assigns ) {
			wp_set_post_terms( $model->id,
				array_values( array_map( function ( $term ) {
					return $term->id;
				}, $assigns ) ),
				$taxonomy,
				$append );
		}
	}
}
