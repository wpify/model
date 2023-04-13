<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\Meta;
use Wpify\Model\Attributes\ReadOnlyProperty;
use Wpify\Model\Attributes\SourceObject;

class User extends Model {
	/**
	 * The user's ID.
	 */
	#[SourceObject( 'ID' )]
	public int $id = 0;

	/**
	 * User's first name.
	 */
	#[Meta('first_name' )]
	public string $first_name = '';

	/**
	 * User's last name.
	 */
	#[Meta('last_name' )]
	public string $last_name = '';

	/**
	 * User's nickname, defaults to the user's username.
	 */
	#[Meta('nickname' )]
	public string $nickname = '';

	/**
	 * User's description.
	 */
	#[Meta('description' )]
	public string $description = '';

	/**
	 * User has enabled rich editing or not.
	 */
	#[Meta('rich_editing' )]
	public bool $rich_editing = true;

	/**
	 * User has enabled syntax highlighting when writing code.
	 */
	#[Meta('syntax_highlighting' )]
	public bool $syntax_highlighting = true;

	/**
	 * User has enabled comment shortcuts or not.
	 */
	#[Meta('comment_shortcuts' )]
	public bool $comment_shortcuts = false;

	/**
	 * User's admin color scheme.
	 */
	#[Meta('admin_color' )]
	public string $admin_color = '';

	/**
	 * User is forced to use SSL to access the admin.
	 */
	#[Meta('use_ssl' )]
	public bool $use_ssl = false;

	/**
	 * Show admin bar on the frontend for the user.
	 */
	#[Meta('show_admin_bar_front' )]
	public bool $show_admin_bar_front = true;

	/**
	 * User's locale.
	 */
	#[Meta('locale' )]
	public string $locale = '';

	/**
	 * User's capabilities.
	 */
	#[Meta('wp_capabilities' )]
	#[ReadOnlyProperty]
	public array $capabilities = array();

	/**
	 * User's level.
	 */
	#[Meta('wp_user_level' )]
	public int $user_level = 0;

	/**
	 * Comma-separated list of dismissed pointers.
	 */
	#[Meta('dismissed_wp_pointers' )]
	#[ReadOnlyProperty]
	public string $dismissed_wp_pointers = '';

	/**
	 * Whether to show the welcome panel or not.
	 */
	#[Meta('show_welcome_panel' )]
	public bool $show_welcome_panel = true;

	/**
	 * The last post ID the user has written.
	 */
	#[Meta('wp_dashboard_quick_press_last_post_id' )]
	#[ReadOnlyProperty]
	public int $dashboard_quick_press_last_post_id = 0;

	/**
	 * The user's login name.
	 */
	#[SourceObject('data.user_login')]
	public string $login = '';

	/**
	 * User's slug
	 */
	#[SourceObject('data.user_nicename')]
	public string $nicename = '';

	/**
	 * User's email address.
	 */
	#[SourceObject('data.user_email')]
	public string $email = '';

	/**
	 * User's URL.
	 */
	#[SourceObject('data.user_url')]
	public string $url = '';

	/**
	 * User's registered date.
	 */
	#[SourceObject('data.user_registered')]
	public string $registered = '';

	/**
	 * User's display name.
	 */
	#[SourceObject('data.display_name')]
	public string $display_name = '';

	/**
	 * User's activation key.
	 */
	#[SourceObject('data.user_activation_key')]
	public string $activation_key = '';

	/**
	 * User's status.
	 */
	#[SourceObject('data.user_status')]
	public int $status = 0;

	/**
	 * The spam status of the user. (multisite only)
	 */
	#[SourceObject('data.spam')]
	#[ReadOnlyProperty]
	public ?string $spam = '';

	/**
	 * The deleted status of the user. (multisite only)
	 */
	#[SourceObject('data.deleted')]
	#[ReadOnlyProperty]
	public ?string $deleted = '';

	/**
	 * Capabilities that the individual user has been granted outside of those inherited from their role.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public array $caps = array();

	/**
	 * User metadata option name.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public string $cap_key = '';

	/**
	 * The roles the user is part of.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public array $roles = array();

	/**
	 * All capabilities the user has, including individual and role based.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public array $allcaps = array();

	/**
	 * The filter context applied to user data fields.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public ?string $filter = null;
}
