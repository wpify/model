<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\ManyToOneRelation;
use Wpify\Model\Attributes\Meta;
use Wpify\Model\Attributes\ReadOnlyProperty;
use Wpify\Model\Attributes\SourceObject;

class Site extends Model {
	/**
	 * Site ID.
	 *
	 * Named "blog" vs. "site" for legacy reasons.
	 */
	#[SourceObject( 'blog_id' )]
	public int $id;

	/**
	 * Domain of the site.
	 */
	#[SourceObject( 'domain' )]
	public string $domain = '';

	/**
	 * Path of the site.
	 */
	#[SourceObject( 'path' )]
	public string $path = '';

	/**
	 * The ID of the site's parent network.
	 *
	 * Named "site" vs. "network" for legacy reasons. An individual site's "site" is
	 * its network.
	 */
	#[SourceObject( 'site_id' )]
	public int $site_id = 0;

	/**
	 * The date and time on which the site was created or registered.
	 */
	#[SourceObject( 'registered' )]
	public string $registered = '0000-00-00 00:00:00';

	/**
	 * The date and time on which site settings were last updated.
	 */
	#[SourceObject( 'last_updated' )]
	public string $last_updated = '0000-00-00 00:00:00';

	/**
	 * Whether the site should be treated as public.
	 */
	#[SourceObject( 'public' )]
	public bool $public = true;

	/**
	 * Whether the site should be treated as archived.
	 */
	#[SourceObject( 'archived' )]
	public bool $archived = false;

	/**
	 * Whether the site should be treated as mature.
	 *
	 * Handling for this does not exist throughout WordPress core, but custom
	 * implementations exist that require the property to be present.
	 */
	#[SourceObject( 'mature' )]
	public bool $mature = false;

	/**
	 * Whether the site should be treated as spam.
	 */
	#[SourceObject( 'spam' )]
	public bool $spam = false;

	/**
	 * Whether the site should be treated as deleted.
	 */
	#[SourceObject( 'deleted' )]
	public bool $deleted = false;

	/**
	 * The language pack associated with this site.
	 */
	#[SourceObject( 'lang_id' )]
	public int $lang_id = 0;

	/**
	 * The site's current theme.
	 */
	#[Meta('site_name')]
	public string $site_name = '';

	/**
	 * The site's administrator's email address.
	 */
	#[Meta('admin_email')]
	public string $admin_email = '';

	/**
	 * The site's administrator's user ID.
	 */
	#[Meta('admin_user_id')]
	public int $admin_user_id = 0;

	/**
	 * The site's administrator's user object.
	 */
	#[ManyToOneRelation(User::class, 'admin_user_id')]
	#[ReadOnlyProperty]
	public ?User $admin_user;

	/**
	 * The site's URL.
	 */
	#[Meta('siteurl')]
	public string $site_url = '';

	/**
	 * Site's administrators.
	 */
	#[Meta('site_admins')]
	public array $site_admins = array();

	/**
	 * Whether the site is the main site in the network.
	 */
	#[Meta('main_site')]
	public bool $is_main_site = false;

	/**
	 * Child sites of the site.
	 */
	#[Meta('blog_count')]
	public int $blog_count = 0;

	/**
	 * Users count of the site.
	 */
	#[Meta('user_count')]
	public int $user_count = 0;
}
