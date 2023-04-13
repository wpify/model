<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\SourceObject;

class Comment extends Model {
	/**
	 * Comment ID.
	 */
	#[SourceObject( 'comment_ID' )]
	public int $id;

	/**
	 * ID of the post the comment is associated with.
	 */
	#[SourceObject( 'comment_post_ID' )]
	public int $post_id = 0;

	/**
	 * Comment author name.
	 */
	#[SourceObject( 'comment_author' )]
	public string $author_name = '';

	/**
	 * Comment author email address.
	 */
	#[SourceObject( 'comment_author_email' )]
	public string $author_email = '';

	/**
	 * Comment author URL.
	 */
	#[SourceObject( 'comment_author_url' )]
	public string $author_url = '';

	/**
	 * Comment author IP address (IPv4 format).
	 */
	#[SourceObject( 'comment_author_IP' )]
	public string $author_ip = '';

	/**
	 * Comment date in YYYY-MM-DD HH:MM:SS format.
	 */
	#[SourceObject( 'comment_date' )]
	public string $date = '0000-00-00 00:00:00';

	/**
	 * Comment GMT date in YYYY-MM-DD HH::MM:SS format.
	 */
	#[SourceObject( 'comment_date_gmt' )]
	public string $date_gmt = '0000-00-00 00:00:00';

	/**
	 * Comment content.
	 */
	#[SourceObject( 'comment_content' )]
	public string $content;

	/**
	 * Comment karma count.
	 */
	#[SourceObject( 'comment_karma' )]
	public int $karma = 0;

	/**
	 * Comment approval status.
	 */
	#[SourceObject( 'comment_approved' )]
	public bool $approved = false;

	/**
	 * Comment author HTTP user agent.
	 */
	#[SourceObject( 'comment_agent' )]
	public string $http_agent = '';

	/**
	 * Comment type.
	 */
	#[SourceObject( 'comment_type' )]
	public string $type = 'comment';

	/**
	 * Parent comment ID.
	 */
	#[SourceObject( 'comment_parent' )]
	public int $comment_parent_id = 0;

	/**
	 * Comment author ID.
	 */
	#[SourceObject( 'user_id' )]
	public int $author_user_id = 0;

	/**
	 * Comment children.
	 */
	#[SourceObject( 'children' )]
	protected array $children = array();
}
