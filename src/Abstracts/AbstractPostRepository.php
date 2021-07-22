<?php

namespace WpifyModel\Abstracts;

use WP_Post;
use WP_Query;
use WpifyModel\CategoryRepository;
use WpifyModel\Exceptions\NotFoundException;
use WpifyModel\Exceptions\NotPersistedException;
use WpifyModel\Interfaces\PostModelInterface;
use WpifyModel\Interfaces\PostRepositoryInterface;
use WpifyModel\Interfaces\TermModelInterface;
use WpifyModel\Interfaces\TermRepositoryInterface;
use WpifyModel\Interfaces\UserRepositoryInterface;
use WpifyModel\PostTagRepository;
use WpifyModel\UserRepository;

abstract class AbstractPostRepository extends AbstractRepository implements PostRepositoryInterface {
	public $query;

	/** @var ?UserRepositoryInterface */
	private $user_repository;

	/** @var ?TermRepositoryInterface */
	private $category_repository;

	/** @var ?TermRepositoryInterface */
	private $post_tag_repository;

	public function get_user_repository(): UserRepositoryInterface {
		if ( empty( $this->user_repository ) ) {
			$this->user_repository = new UserRepository();
		}

		return $this->user_repository;
	}

	public function get_category_repository(): TermRepositoryInterface {
		if ( empty( $this->category_repository ) ) {
			$this->category_repository = new CategoryRepository();
		}

		return $this->category_repository;
	}

	public function get_post_tag_repository(): TermRepositoryInterface {
		if ( empty( $this->post_tag_repository ) ) {
			$this->post_tag_repository = new PostTagRepository();
		}

		return $this->post_tag_repository;
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
		$args = array(
			'posts_per_page' => - 1,
			'post_status'    => 'any'
		);

		return $this->find( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function find( array $args = array() ) {
		$defaults    = array(
			'post_type'      => $this::post_type(),
			'post_status'    => 'any',
			'posts_per_page' => - 1,
		);
		$args        = wp_parse_args( $args, $defaults );
		$this->query = new WP_Query( $args );
		$collection  = array();

		while ( $this->query->have_posts() ) {
			$this->query->the_post();

			global $post;

			$collection[] = $this->factory( $post );
		}

		wp_reset_postdata();

		return $this->collection_factory( $collection );
	}

	/**
	 * @return string
	 */
	abstract static function post_type(): string;

	public function published() {
		$args = array(
			'posts_per_page' => - 1,
			'post_status'    => 'publish'
		);

		return $this->find( $args );
	}

	public function all_by_term( TermModelInterface $term ) {
		$args = array(
			'tax_query' => array(
				array(
					'taxonomy' => $term->repository()->taxonomy,
					'field'    => 'term_id',
					'terms'    => array( $term->id ),
					'operator' => '=',
				)
			),
		);

		return $this->find( $args );
	}

	/**
	 * @return AbstractPostModel
	 */
	public function create() {
		return $this->factory( null );
	}

	/**
	 * @param AbstractPostModel $model
	 *
	 * @return mixed
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
			} elseif ( $prop['source'] === 'relation' && isset( $prop['relation'] ) && method_exists( $prop['relation'], 'assign' ) && $prop['changed'] ) {
				$prop['relation']->assign();
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
	 * @return WP_Post
	 * @throws NotFoundException
	 */
	protected function resolve_object( $data ): WP_Post {
		if ( is_object( $data ) && get_class( $data ) === $this::model() ) {
			$object = $data->source_object();
		} elseif ( $data instanceof WP_Post ) {
			$object = $data;
		} elseif ( is_null( $data ) ) {
			$object = new WP_Post( (object) array(
				'ID'            => null,
				'post_author'   => get_current_user_id(),
				'post_date'     => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', 1 ),
				'post_type'     => $this::post_type(),
			) );
		} elseif ( isset( $data->id ) ) {
			$object = get_post( $data->id );
		} else {
			$object = get_post( $data );
		}

		if ( ! ( $object instanceof WP_Post ) ) {
			throw new NotFoundException( 'The post was not found' );
		}

		return $object;
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
		$to_assign = array();

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

	public function get_paginate_links( $args = array() ) {
		$pagination   = $this->get_pagination();
		$default_args = array( 'total' => $pagination['total_pages'], 'current' => $pagination['current_page'] );
		$args         = wp_parse_args( $args, $default_args );

		return paginate_links( $args );
	}

	public function get_pagination(): array {
		return array(
			'found_posts'  => $this->query->found_posts,
			'current_page' => $this->query->query_vars['paged'] ?: 1,
			'total_pages'  => $this->query->max_num_pages,
			'per_page'     => $this->query->query_vars['posts_per_page']
		);
	}
}
