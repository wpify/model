<?php
declare( strict_types=1 );

namespace Wpify\Model;

use WP_Comment;
use Wpify\Model\Attributes\Meta;
use Wpify\Model\Attributes\SourceObject;
use Wpify\Model\Exceptions\CouldNotSaveModelException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Interfaces\ModelInterface;

class CommentRepository extends Repository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Comment::class;
	}

	/**
	 * Returns the Comment model by the WP_Comment object or id.
	 *
	 * @param mixed $source
	 *
	 * @return ModelInterface|null
	 * @throws Exceptions\RepositoryNotInitialized
	 */
	public function get( mixed $source ): ?ModelInterface {
		$wp_comment = null;
		$comment    = null;

		if ( $source instanceof WP_Comment ) {
			$wp_comment = $source;
		}

		if ( ! $wp_comment ) {
			$comment = $this->storage()->get( $source );
		}

		if ( $comment ) {
			return $comment;
		}

		if ( ! $wp_comment ) {
			$wp_comment = get_comment( $source );
		}

		if ( $wp_comment ) {
			$model_class = $this->model();
			$comment     = new $model_class( $this->manager() );

			$comment->source( $wp_comment );
			$this->storage()->save( $comment->id, $comment );
		}

		return $comment;
	}

	/**
	 * Saves the comment to the database.
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
				$model->{'persist_' . $prop['name']}( $prop['value'] );
			} elseif ( $source instanceof SourceObject ) {
				$key          = $source->key ?? $prop['name'];
				$data[ $key ] = $prop['value'];
			} elseif ( $source instanceof Meta ) {
				$key                          = $source->key ?? $prop['name'];
				$data['comment_meta'][ $key ] = $prop['value'];
			}
		}

		if ( $data['comment_ID'] > 0 ) {
			$result = wp_update_comment( $data, true );
		} else {
			$result = wp_insert_comment( $data );
		}

		if ( is_wp_error( $result ) ) {
			throw new CouldNotSaveModelException( $result->get_error_message() );
		}

		$model->refresh( get_user_by( 'id', $result ) );
		$this->storage()->delete( $model->id );

		return $model;
	}

	/**
	 * Deletes the comment from the database.
	 *
	 * @param ModelInterface $model
	 *
	 * @return bool
	 */
	public function delete( ModelInterface $model ): bool {
		$this->storage()->delete( $model->id );

		return wp_delete_comment( $model->id, true );
	}

	/**
	 * Returns a collection of comments.
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_comments/
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws Exceptions\RepositoryNotInitialized
	 */
	public function find( array $args = array() ): array {
		$comments  = get_comments( $args );
		$collation = array();

		foreach ( $comments as $comment ) {
			$collation[] = $this->get( $comment );
		}

		return $collation;
	}

	/**
	 * Returns a collection of comments by the post id.
	 *
	 * @param int $post_id
	 *
	 * @return array
	 * @throws Exceptions\RepositoryNotInitialized
	 */
	public function find_by_post_id( int $post_id ): array {
		return $this->find( array( 'post_id' => $post_id ) );
	}

	/**
	 * Find all comments with the given ids.
	 *
	 * @param array $ids
	 * @param array $args
	 *
	 * @return Comment[]
	 * @throws RepositoryNotInitialized
	 */
	public function find_by_ids( array $ids, array $args = array() ): array {
		return $this->find( array_merge( $args, array(
			'comment__in' => $ids
		) ) );
	}

	/**
	 * Returns a collection of all comments.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws RepositoryNotInitialized
	 */
	public function find_all( array $args = [] ): array {
		return $this->find( $args );
	}
}
