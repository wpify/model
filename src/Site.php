<?php

namespace Wpify\Model;


use Wpify\Model\Abstracts\AbstractModel;

/**
 * @method \WP_Site source_object()
 */
class Site extends AbstractModel {
	/**
	 * Site ID.
	 *
	 * Named "blog" vs. "site" for legacy reasons.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $blog_id;

	/**
	 * Domain of the site.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $domain = '';

	/**
	 * Path of the site.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $path = '';

	/**
	 * The ID of the site's parent network.
	 *
	 * Named "site" vs. "network" for legacy reasons. An individual site's "site" is
	 * its network.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $site_id = '0';

	/**
	 * The date and time on which the site was created or registered.
	 *
	 * @since 4.5.0
	 * @var string Date in MySQL's datetime format.
	 */
	public $registered = '0000-00-00 00:00:00';

	/**
	 * The date and time on which site settings were last updated.
	 *
	 * @since 4.5.0
	 * @var string Date in MySQL's datetime format.
	 */
	public $last_updated = '0000-00-00 00:00:00';

	/**
	 * Whether the site should be treated as public.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $public = '1';

	/**
	 * Whether the site should be treated as archived.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $archived = '0';

	/**
	 * Whether the site should be treated as mature.
	 *
	 * Handling for this does not exist throughout WordPress core, but custom
	 * implementations exist that require the property to be present.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $mature = '0';

	/**
	 * Whether the site should be treated as spam.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $spam = '0';

	/**
	 * Whether the site should be treated as deleted.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $deleted = '0';

	/**
	 * The language pack associated with this site.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.5.0
	 * @var string
	 */
	public $lang_id = '0';

	protected $_props = array(
		'id'           => array(
			'source'      => 'object',
			'source_name' => 'blog_id'
		),
		'domain'       => array(
			'source'      => 'object',
			'source_name' => 'domain'
		),
		'path'         => array(
			'source'      => 'object',
			'source_name' => 'path'
		),
		'site_id'      => array(
			'source'      => 'object',
			'source_name' => 'site_id'
		),
		'registered'   => array(
			'source'      => 'object',
			'source_name' => 'registered'
		),
		'last_updated' => array(
			'source'      => 'object',
			'source_name' => 'last_updated'
		),
		'public'       => array(
			'source'      => 'object',
			'source_name' => 'public'
		),
		'archived'     => array(
			'source'      => 'object',
			'source_name' => 'archived'
		),
		'mature'       => array(
			'source'      => 'object',
			'source_name' => 'mature'
		),
		'spam'         => array(
			'source'      => 'object',
			'source_name' => 'spam'
		),
		'deleted'      => array(
			'source'      => 'object',
			'source_name' => 'deleted'
		),
		'lang_id'      => array(
			'source'      => 'object',
			'source_name' => 'lang_id'
		),
	);

	static function meta_type() {
		return 'blog';
	}

	public function model_repository() {
		// TODO: Implement model_repository() method.
	}
}
