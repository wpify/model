<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Interfaces\ModelInterface;

/**
 * @method Page|null get( mixed $source )
 * @method Page create( array $data )
 * @method Page save( Page $model )
 * @method bool delete( Page $model )
 * @method Page[] find( array $args = [] )
 * @method Page[] find_all( array $args = array() )
 * @method Page[] find_published( array $args = array() )
 * @method Page[] find_all_by_term( Page $model )
 */
class PageRepository extends PostRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Page::class;
	}

	/**
	 * Returns post types for the repository.
	 *
	 * @return string[]
	 */
	public function post_types(): array {
		return array( 'page' );
	}
}
