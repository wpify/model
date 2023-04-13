<?php
declare( strict_types=1 );

namespace Wpify\Model;

/**
 * Post tag repository.
 *
 * @method ProductCat create( array $data )
 * @method ProductCat|null get( mixed $source )
 * @method ProductCat save( ProductCat $model )
 * @method bool delete( ProductCat $model )
 * @method ProductCat[] find( array $args )
 * @method ProductCat[] all()
 * @method ProductCat[] not_empty( array $args = array() )
 * @method ProductCat[] children_of( int $parent_id = 0, array $args = array() )
 * @method ProductCat[] terms_of_post( int $post_id )
 */
class ProductCatRepository extends TermRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return ProductCat::class;
	}

	/**
	 * Returns taxonomy for the repository.
	 *
	 * @return string
	 */
	public function taxonomy(): string {
		return 'product_cat';
	}
}
