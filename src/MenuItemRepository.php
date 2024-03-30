<?php
declare( strict_types=1 );

namespace Wpify\Model;

/**
 * Menu item repository.
 *
 * Don't use it directly, use MenuRepository instead.
 *
 * @internal This class is not part of the public API.
 *
 * @method MenuItem|null get( mixed $source )
 * @method MenuItem create( array $data )
 * @method MenuItem save( MenuItem $model )
 * @method bool delete( MenuItem $model, bool $force_delete = true )
 * @method MenuItem[] find( array $args = [] )
 * @method MenuItem[] find_all( array $args = array() )
 * @method MenuItem[] find_published( array $args = array() )
 */
class MenuItemRepository extends PostRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return MenuItem::class;
	}

	/**
	 * Returns post types for the repository.
	 *
	 * @return string[]
	 */
	public function post_types(): array {
		return array( 'nav_menu_item' );
	}
}
