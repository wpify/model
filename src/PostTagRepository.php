<?php
declare( strict_types=1 );

namespace Wpify\Model;

/**
 * Post tag repository.
 *
 * @method PostTag create( array $data )
 * @method PostTag|null get( mixed $source )
 * @method PostTag save( PostTag $model )
 * @method bool delete( PostTag $model )
 * @method PostTag[] find( array $args )
 * @method PostTag[] find_all()
 * @method PostTag[] find_not_empty( array $args = array() )
 * @method PostTag[] find_children_of( int $parent_id = 0, array $args = array() )
 * @method PostTag[] find_terms_of_post( int $post_id )
 */
class PostTagRepository extends TermRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return PostTag::class;
	}

	/**
	 * Returns taxonomy for the repository.
	 *
	 * @return string
	 */
	public function taxonomy(): string {
		return 'post_tag';
	}
}
