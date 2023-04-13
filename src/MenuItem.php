<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\ReadOnlyProperty;
use Wpify\Model\Attributes\SourceObject;

class MenuItem extends Post {
	/**
	 * The DB ID of this menu item.
	 */
	#[SourceObject]
	public int $db_id = 0;

	/**
	 * The ID of the menu item's parent if any.
	 */
	#[SourceObject]
	public int $menu_item_parent = 0;

	/**
	 * The DB ID of the original object this menu item represents, e.g. ID for posts and term_id for categories.
	 */
	#[SourceObject]
	public int $object_id = 0;

	/**
	 * The type of object originally represented, such as "category", "post", or "attachment".
	 */
	#[SourceObject]
	public string $object = '';

	/**
	 * Custom.
	 */
	#[SourceObject]
	public string $custom = '';

	/**
	 * The type of menu item.
	 */
	#[SourceObject]
	public string $type_label = '';

	/**
	 * The title of the menu item.
	 */
	#[SourceObject]
	public string $title = '';

	/**
	 * The URL to which this menu item points, if it is a custom link.
	 */
	#[SourceObject]
	public string $url = '';

	/**
	 * The target attribute of the link element for this menu item.
	 */
	#[SourceObject]
	public string $target = '';

	/**
	 * The title attribute of the link element for this menu item.
	 */
	#[SourceObject]
	public string $attr_title = '';

	/**
	 * The description of this menu item.
	 */
	#[SourceObject]
	public string $description = '';

	/**
	 * CSS classes to be added to the menu item's <li>.
	 */
	#[SourceObject]
	public array $classes = array();

	/**
	 * Link relationship (XFN).
	 */
	#[SourceObject]
	public string $xfn = '';

	/**
	 * Menu items that are children of this menu item.
	 *
	 * @var MenuItem[]
	 */
	#[ReadOnlyProperty]
	public array $children = array();

	/**
	 * @deprecated Use $children instead
	 * @var MenuItem[]
	 */
	#[ReadOnlyProperty]
	public array $item_children = array();

	/**
	 * Whether this menu item is the current menu item or not.
	 */
	#[ReadOnlyProperty]
	public bool $is_current = false;

	/**
	 * Whether this menu item or any of its children are the current menu item or not.
	 */
	#[ReadOnlyProperty]
	public bool $is_highlighted = false;

	/**
	 * Whether this menu item is the current menu item or not.
	 *
	 * @return bool
	 */
	public function get_is_current(): bool {
		return $this->object_id === get_the_ID();
	}

	/**
	 * Whether this menu item or any of its children are the current menu item or not.
	 *
	 * @return bool
	 */
	public function get_is_highlighted(): bool {
		if ( $this->is_current ) {
			return true;
		}

		foreach ( $this->children as $child ) {
			if ( $child->is_highlighted ) {
				return true;
			}
		}

		return false;
	}
}
