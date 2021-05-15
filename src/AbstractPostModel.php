<?php

namespace WpifyModel;

use WP_Error;
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
	 * @return int|WP_Error
	 */
	public function save() {
		$object_data = array();

		foreach ( $this->get_props() as $key => $prop ) {
			$source_name = $prop['source_name'];

			if ( $prop['source'] === 'object' ) {
				$object_data[ $source_name ] = $this->$key;
			} elseif ( $prop['source'] === 'meta' ) {
				if ( ! isset( $object_data['meta_input'] ) ) {
					$object_data['meta_input'] = array();
				}

				$object_data['meta_input'][ $source_name ] = $this->$key;
			}
		}

		if ( $this->id > 0 ) {
			$result = wp_update_post( $object_data, true );
		} else {
			$result = wp_insert_post( $object_data, true );
		}

		if ( ! is_wp_error( $result ) ) {
			$this->refresh( $result );
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
	 * @return ?WP_Post
	 * @throws NotFoundException
	 */
	protected function object( $object = null ) {
		if ( $object instanceof WP_Post ) {
			$new_object = $object;
		} elseif ( is_null( $object ) ) {
			$new_object = new WP_Post( (object) array(
				'ID'                    => null,
				'post_author'           => get_current_user_id(),
				'post_date'             => current_time( 'mysql' ),
				'post_date_gmt'         => current_time( 'mysql', 1 ),
				'post_content'          => $this->content,
				'post_title'            => $this->title,
				'post_excerpt'          => $this->excerpt,
				'post_status'           => $this->status,
				'comment_status'        => $this->comment_status,
				'ping_status'           => $this->ping_status,
				'post_password'         => $this->password,
				'post_name'             => $this->slug,
				'to_ping'               => $this->to_ping,
				'pinged'                => $this->pinged,
				'post_modified'         => $this->modified_at,
				'post_modified_gmt'     => $this->modified_at_gmt,
				'post_content_filtered' => $this->content_filtered,
				'post_parent'           => $this->parent_id,
				'guid'                  => $this->guid,
				'menu_order'            => $this->menu_order,
				'post_type'             => $this->post_type,
				'post_mime_type'        => $this->mime_type,
				'comment_count'         => $this->comment_count,
				'filter'                => 'raw',
			) );
		} elseif ( isset( $object->id ) ) {
			$new_object = get_post( $object->id );
		} else {
			$new_object = get_post( $object );
		}

		if ( ! is_object( $new_object ) ) {
			throw new NotFoundException( 'The post was not found' );
		}

		return $new_object;
	}
}
