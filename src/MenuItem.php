<?php

namespace Wpify\Model;

use Wpify\Model\Abstracts\AbstractPostModel;

/**
 * Class BasicPost
 * @package Wpify\Model
 */
class MenuItem extends AbstractPostModel {
	/**
	 * @var int
	 */
	public $menu_item_parent;
	/**
	 * @var array
	 */
	public $item_children;

	protected $_props = array(
		'id'               => array( 'source' => 'object', 'source_name' => 'ID' ),
		'author_id'        => array( 'source' => 'object', 'source_name' => 'post_author' ),
		'date'             => array( 'source' => 'object', 'source_name' => 'post_date' ),
		'date_gmt'         => array( 'source' => 'object', 'source_name' => 'post_date_gmt' ),
		'content'          => array( 'source' => 'object', 'source_name' => 'post_content' ),
		'excerpt'          => array( 'source' => 'object', 'source_name' => 'post_excerpt' ),
		'status'           => array( 'source' => 'object', 'source_name' => 'post_status' ),
		'password'         => array( 'source' => 'object', 'source_name' => 'post_password' ),
		'slug'             => array( 'source' => 'object', 'source_name' => 'post_name' ),
		'modified_at'      => array( 'source' => 'object', 'source_name' => 'post_modified' ),
		'modified_at_gmt'  => array( 'source' => 'object', 'source_name' => 'post_modified_gmt' ),
		'content_filtered' => array( 'source' => 'object', 'source_name' => 'post_content_filtered' ),
		'parent_id'        => array( 'source' => 'object', 'source_name' => 'post_parent' ),
		'mime_type'        => array( 'source' => 'object', 'source_name' => 'post_mime_type' ),
		'menu_item_parent' => array( 'source' => 'object', 'source_name' => 'menu_item_parent' ),
		'title'            => array( 'source' => 'object', 'source_name' => 'title' ),
		'url'              => array( 'source' => 'object', 'source_name' => 'url' ),
		'classes'          => array( 'source' => 'object', 'source_name' => 'classes' ),
		'attr_title'       => array( 'source' => 'object', 'source_name' => 'attr_title' ),
		'target'           => array( 'source' => 'object', 'source_name' => 'target' ),
		'item_children'    => array( 'source' => 'custom' ),
	);

	public function get_children() {
		return $this->item_children;
	}

	public function to_array( array $props = array() ): array {
		$data = parent::to_array( $props );
		if ( $data['children'] ) {
			unset( $data['item_children'] );
			$data['children'] = array_map( function ( $item ) {
				return $item->to_array();
			}, $data['children'] );
		}

		return $data;
	}
}
