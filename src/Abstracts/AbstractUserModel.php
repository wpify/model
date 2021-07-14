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
	 * User first name
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $first_name = '';

	/**
	 * User last name
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $last_name = '';

	protected $_props = array(
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
		'first_name'     => array( 'source' => 'meta', 'source_name' => 'first_name' ),
		'last_name'      => array( 'source' => 'meta', 'source_name' => 'last_name' ),
	);

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'user';
	}
}
