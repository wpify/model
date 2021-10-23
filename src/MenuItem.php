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
	public $item_children = [];

	protected $_props = array(
		'id'               => array( 'source' => 'object', 'source_name' => 'ID' ),
		'author_id'        => array( 'source' => 'object', 'source_name' => 'post_author' ),
		'date'             => array( 'source' => 'object', 'source_name' => 'post_date' ),
		'date_gmt'         => array( 'source' => 'object', 'source_name' => 'post_date_gmt' ),
		'content'          => array( 'source' => 'object', 'source_name' => 'post_content' ),
		'title'            => array( 'source' => 'object', 'source_name' => 'post_title' ),
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
	);
}
