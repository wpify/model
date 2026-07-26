<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Menu;
use Wpify\Model\MenuItem;
use Wpify\Model\MenuItemRepository;
use Wpify\Model\MenuRepository;
use Wpify\Model\Tests\TestCase;

class MenusTest extends TestCase {
	private int $menu_id;
	private int $post_id;
	private int $parent_item_id;
	private int $child_item_id;

	public function set_up(): void {
		parent::set_up();

		$this->menu_id = wp_create_nav_menu( 'Primary menu' );
		$this->post_id = self::factory()->post->create( array( 'post_title' => 'Linked post' ) );

		$this->parent_item_id = wp_update_nav_menu_item(
			$this->menu_id,
			0,
			array(
				'menu-item-title'  => 'Parent link',
				'menu-item-url'    => 'https://example.org/parent',
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
		);

		$this->child_item_id = wp_update_nav_menu_item(
			$this->menu_id,
			0,
			array(
				'menu-item-title'     => 'Child link',
				'menu-item-object'    => 'post',
				'menu-item-object-id' => $this->post_id,
				'menu-item-type'      => 'post_object',
				'menu-item-parent-id' => $this->parent_item_id,
				'menu-item-status'    => 'publish',
			)
		);

		register_nav_menu( 'primary', 'Primary' );
		set_theme_mod( 'nav_menu_locations', array( 'primary' => $this->menu_id ) );
	}

	private function menus(): MenuRepository {
		return $this->repo( MenuRepository::class );
	}

	public function test_model_and_taxonomy(): void {
		$this->assertSame( Menu::class, $this->menus()->model() );
		$this->assertSame( 'nav_menu', $this->menus()->taxonomy() );
	}

	public function test_get_by_id_and_by_location(): void {
		$by_id = $this->menus()->get( $this->menu_id );
		$this->assertInstanceOf( Menu::class, $by_id );
		$this->assertSame( $this->menu_id, $by_id->id );

		$by_location = $this->menus()->get( 'primary' );
		$this->assertInstanceOf( Menu::class, $by_location );
		$this->assertSame( $this->menu_id, $by_location->id );
	}

	/**
	 * Getting a menu that does not exist fatals instead of returning null.
	 *
	 * @see https://github.com/wpify/model/issues/1
	 */
	public function test_get_returns_null_for_missing_menu(): void {
		$this->markTestSkipped( 'MenuRepository::get() fatals for unassigned/missing menus — https://github.com/wpify/model/issues/1' );
	}

	public function test_menu_items_relation_builds_sorted_tree(): void {
		$menu = $this->menus()->get( $this->menu_id );

		$this->assertCount( 1, $menu->children, 'only top-level items at the root' );

		$parent = $menu->children[0];
		$this->assertInstanceOf( MenuItem::class, $parent );
		$this->assertSame( 'Parent link', $parent->title );

		$this->assertCount( 1, $parent->children );
		$this->assertSame( 'Child link', $parent->children[0]->title );

		$this->assertSame( $menu->children, $menu->items, 'items is an alias of children' );
	}

	public function test_items_returns_objects_or_arrays(): void {
		$objects = $this->menus()->items( $this->menu_id );
		$this->assertInstanceOf( MenuItem::class, $objects[0] );

		$arrays = $this->menus()->items( $this->menu_id, MenuRepository::RETURN_ARRAY );
		$this->assertIsArray( $arrays[0] );
		$this->assertSame( 'Parent link', $arrays[0]['title'] );
	}

	public function test_menu_to_array_recurses_children(): void {
		$data = $this->menus()->get( $this->menu_id )->to_array();

		$this->assertIsArray( $data['children'][0] );
		$this->assertSame( 'Parent link', $data['children'][0]['title'] );
		$this->assertIsArray( $data['children'][0]['children'][0] );
	}

	public function test_menu_item_repository(): void {
		$repository = $this->repo( MenuItemRepository::class );

		$this->assertSame( MenuItem::class, $repository->model() );
		$this->assertSame( array( 'nav_menu_item' ), $repository->post_types() );
	}

	public function test_menu_item_is_current_and_is_highlighted(): void {
		$menu = $this->menus()->get( $this->menu_id );

		$parent = $menu->children[0];
		$child  = $parent->children[0];

		$this->assertFalse( $child->get_is_current() );
		$this->assertFalse( $parent->get_is_highlighted() );

		// Visit the linked post: the child becomes current, the parent highlighted.
		$GLOBALS['post'] = get_post( $this->post_id );

		$fresh  = $this->menus()->get( $this->menu_id );
		$parent = $fresh->children[0];
		$child  = $parent->children[0];

		$this->assertTrue( $child->get_is_current() );
		$this->assertTrue( $child->is_current );
		$this->assertTrue( $parent->get_is_highlighted() );

		unset( $GLOBALS['post'] );
	}
}
