<?php

namespace WpifyModel\Abstracts;

/**
 * Class AbstractPostModel
 * @package WpifyModel
 */
abstract class AbstractUserModel extends AbstractModel {
	/**
	 * User ID.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $id;

	/**
	 * User login
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $login = '';

	/**
	 * User pass
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $pass = '';

	/**
	 * User nicename
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $nicename = '';

	/**
	 * User email
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $email = '';

	/**
	 * User url
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $url = '';

	/**
	 * User registered
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $registered = '0000-00-00 00:00:00';

	/**
	 * User activation key
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $activation_key = '';

	/**
	 * User status
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $status = 0;

	/**
	 * User display name
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $display_name = '';

	/**
	 * ID of a post's parent post.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Parent post
	 *
	 * @var self
	 */
	public $parent;

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'user';
	}

	/**
	 * @param array $props
	 *
	 * @return array
	 */
	protected function props( array $props = array() ): array {
		return array_merge( $props, array(
			'id'             => array( 'source' => 'object', 'source_name' => 'ID' ),
			'login'          => array( 'source' => 'object', 'source_name' => 'user_login' ),
			'pass'           => array( 'source' => 'object', 'source_name' => 'user_pass' ),
			'nicename'       => array( 'source' => 'object', 'source_name' => 'user_nicename' ),
			'email'          => array( 'source' => 'object', 'source_name' => 'user_email' ),
			'url'            => array( 'source' => 'object', 'source_name' => 'user_url' ),
			'registered'     => array( 'source' => 'object', 'source_name' => 'user_registered' ),
			'activation_key' => array( 'source' => 'object', 'source_name' => 'user_activation_key' ),
			'status'         => array( 'source' => 'object', 'source_name' => 'user_status' ),
			'display_name'   => array( 'source' => 'object', 'source_name' => 'display_name' ),
		) );
	}

	protected function set_parent( ?AbstractPostModel $parent = null ) {
		if ( $parent ) {
			$this->parent_id = $parent->id;
			$this->parent    = $parent;
		} else {
			unset( $this->parent_id );
			unset( $this->parent );
		}
	}

	protected function set_parent_id( ?int $parent_id = null ) {
		unset( $this->parent );
		$this->parent_id = $parent_id;
	}
}
