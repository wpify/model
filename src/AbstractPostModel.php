<?php

namespace WpifyModel;

use stdClass;
use WP_Post;

/**
 * Class AbstractPostModel
 * @package WpifyModel
 */
abstract class AbstractPostModel extends AbstractModel {
	/**
	 * Post ID.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $id;

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $author_id = 0;

	/**
	 * The post's local publication time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post's content.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $content = '';

	/**
	 * The post's title.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $title = '';

	/**
	 * The post's excerpt.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $excerpt = '';

	/**
	 * The post's status.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $status = 'publish';

	/**
	 * Whether comments are allowed.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 * Whether pings are allowed.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 * The post's password in plain text.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $password = '';

	/**
	 * The post's slug.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $slug = '';

	/**
	 * URLs queued to be pinged.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $to_ping = '';

	/**
	 * URLs that have been pinged.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $pinged = '';

	/**
	 * The post's local modified time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $modified_at = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $modified_at_gmt = '0000-00-00 00:00:00';

	/**
	 * A utility DB field for post content.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $content_filtered = '';

	/**
	 * ID of a post's parent post.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * The unique identifier for a post, not necessarily a URL, used as the feed GUID.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $guid = '';

	/**
	 * A field used for ordering posts.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * The post's type, like post or page.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * An attachment's mime type.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $comment_count = 0;

	/**
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $filter;

	/**
	 * @return bool
	 */
	public function save() {
		$object_data = array(
			'meta_input' => array(),
		);

		foreach ( $this->get_props() as $key => $prop ) {
			$source_name = $prop['source_name'];

			if ( $prop['source'] === 'object' ) {
				$object_data[ $source_name ] = $this->$key;
			} elseif ( $prop['source'] === 'meta' ) {
				$object_data['meta_input'][ $source_name ] = $this->$key;
			}
		}

		$result = wp_update_post( $object_data );

		if ( ! is_wp_error( $result ) ) {
			$this->id = $result;
			$this->refresh();
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function meta_type() {
		return 'post';
	}

	/**
	 * @param array $props
	 *
	 * @return array
	 */
	protected function props( array $props = array() ): array {
		return array_merge( $props, array(
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
		) );
	}

	/**
	 * @param $object
	 *
	 * @return array|stdClass|WP_Post|null
	 */
	protected function object( $object ) {
		if ( is_null( $object ) ) {
			$object                = new stdClass();
			$object->ID            = 0;
			$object->post_author   = get_current_user_id();
			$object->post_date     = current_time( 'mysql' );
			$object->post_date_gmt = current_time( 'mysql', 1 );
			$object->post_type     = $this->post_type;
			$object->filter        = 'raw';
		} elseif ( $object instanceof WP_Post ) {
			$object = get_post( $object->ID );
		} elseif ( $object instanceof AbstractPostModel ) {
			$object = get_post( $object->id );
		} else {
			$object = get_post( $object );
		}

		return $object;
	}
}
