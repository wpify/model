<?php

namespace Wpify\Model;

use Wpify\Model\Abstracts\AbstractTermRepository;

/**
 * Class BasePageRepository
 *
 * @package Wpify\Model
 *
 */
class MenuRepository extends AbstractTermRepository {
	public function model(): string {
		return Menu::class;
	}

	protected function factory( $object ) {
		$class = $this->model();

		return new $class( $this->resolve_object( $object ), $this );
	}

	protected function resolve_object( $data = null ): ?\WP_Term {
		$menu_id   = null;
		$locations = get_nav_menu_locations();
		if ( $data != 0 && is_numeric( $data ) ) {
			$menu_id = $data;
		} elseif ( is_array( $locations ) && ! empty( $locations ) ) {
			$menu_id = $this->get_menu_id_from_locations( $data, $locations );
		} elseif ( $data === false ) {
			$menu_id = false;
		}
		if ( ! $menu_id ) {

			$menu_id = $this->get_menu_id_from_terms( $data );
		}

		return parent::resolve_object( $menu_id );
	}

	/**
	 * @param string $slug
	 * @param array  $locations
	 *
	 * @return integer
	 * @internal
	 */
	protected function get_menu_id_from_locations( $slug, $locations ) {
		if ( $slug === 0 ) {
			$slug = $this->get_menu_id_from_terms( $slug );
		}
		if ( is_numeric( $slug ) ) {
			$slug = array_search( $slug, $locations );
		}
		if ( isset( $locations[ $slug ] ) ) {
			$menu_id = $locations[ $slug ];
			if ( function_exists( 'wpml_object_id_filter' ) ) {
				$menu_id = wpml_object_id_filter( $locations[ $slug ], 'nav_menu' );
			}

			return $menu_id;
		}
	}

	/**
	 * @param int $slug
	 *
	 * @return int
	 * @internal
	 */
	protected function get_menu_id_from_terms( $slug = 0 ) {
		if ( ! is_numeric( $slug ) && is_string( $slug ) ) {
			// we have a string so lets search for that
			$menu = get_term_by( 'slug', $slug, 'nav_menu' );
			if ( $menu ) {
				return $menu->term_id;
			}
			$menu = get_term_by( 'name', $slug, 'nav_menu' );
			if ( $menu ) {
				return $menu->term_id;
			}
		}
		$menus = get_terms( 'nav_menu', array( 'hide_empty' => true ) );
		if ( is_array( $menus ) && count( $menus ) ) {
			if ( isset( $menus[0]->term_id ) ) {
				return $menus[0]->term_id;
			}
		}

		return 0;
	}

	public function get_menu_item_repository() {
		// TODO: cache this
		return new MenuItemRepository();
	}

	public function taxonomy(): string {
		return 'nav_menu';
	}
}
