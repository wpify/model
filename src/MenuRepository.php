<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Exceptions\RepositoryNotFoundException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;

/**
 * Repository for the menu.
 *
 * @method Menu create( array $data )
 * @method Menu save( Menu $model )
 * @method bool delete( Menu $model, bool $force_delete = true )
 * @method Menu[] find( array $args )
 * @method Menu[] find_all()
 * @method Menu[] find_not_empty( array $args = array() )
 * @method Menu[] find_children_of( int $parent_id = 0, array $args = array() )
 * @method Menu[] find_terms_of_post( int $post_id )
 */
class MenuRepository extends TermRepository {
	public const RETURN_ARRAY = 'array';
	public const RETURN_OBJECTS = 'objects';

	private array $menu_locations = array();

	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Menu::class;
	}

	/**
	 * Returns taxonomy for the repository.
	 *
	 * @return string
	 */
	public function taxonomy(): string {
		return 'nav_menu';
	}

	/**
	 * Returns the Menu model by the location, WP_Term object, id, slug or name.
	 *
	 * @param mixed $source
	 *
	 * @return Menu|null
	 * @throws Exceptions\RepositoryNotInitialized
	 * @throws RepositoryNotFoundException
	 */
	public function get( mixed $source ): ?Menu {
		if ( empty( $this->menu_locations ) ) {
			$this->menu_locations = get_nav_menu_locations();
		}

		$wp_menu     = null;
		$model_class = $this->model();

		if ( is_string( $source ) && in_array( $source, array_keys( $this->menu_locations ) ) ) {
			$wp_menu = wp_get_nav_menu_object( $this->menu_locations[ $source ] );
		}

		if ( $wp_menu ) {
			$menu = parent::get( $wp_menu );
		} else {
			$term = parent::get( $source );
			$menu = $term ? new $model_class( $this->manager() ) : null;

			$menu->source( $term->source() );
		}

		return $menu;
	}

	/**
	 * Returns the menu items by location, WP_Term object, id, slug or name.
	 *
	 * @param mixed $source Menu location, WP_Term object, id, slug or name.
	 * @param string $return Return type, can be MenuRepository::RETURN_OBJECTS or MenuRepository::RETURN_ARRAY.
	 *
	 * @return array|MenuItem[]
	 * @throws RepositoryNotInitialized
	 * @throws RepositoryNotFoundException
	 */
	public function items( mixed $source, string $return = self::RETURN_OBJECTS ): array {
		$items = array();
		$menu  = $this->get( $source );

		if ( $menu ) {
			if ( $return === self::RETURN_OBJECTS ) {
				return $menu->children;
			} elseif ( $return === self::RETURN_ARRAY ) {
				$menu = $menu->to_array();

				return $menu['children'];
			}
		}

		return $items;
	}
}
