<?php
declare( strict_types=1 );

namespace Wpify\Model;

/**
 * @method Category create( array $data )
 * @method Category|null get( mixed $source )
 * @method Category save( Category $model )
 * @method bool delete( Category $model )
 * @method Category[] find( array $args )
 * @method Category[] find_all()
 * @method Category[] find_not_empty( array $args = array() )
 * @method Category[] find_children_of( int $parent_id = 0, array $args = array() )
 * @method Category[] find_terms_of_post( int $post_id )
 */
class CategoryRepository extends TermRepository {
	const TAXONOMY = 'category';
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Category::class;
	}

	/**
	 * Returns taxonomy for the repository.
	 *
	 * @return string
	 */
	public function taxonomy(): string {
		return self::TAXONOMY;
	}
}
