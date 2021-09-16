<?php

namespace Wpify\Model\Abstracts;

use Wpify\Model\Interfaces\PostModelInterface;
use Wpify\Model\Interfaces\PostRepositoryInterface;
use Wpify\Model\Interfaces\TermModelInterface;
use Wpify\Model\Relations\PostAuthorRelation;
use Wpify\Model\Relations\PostChildrenPostsRelation;
use Wpify\Model\Relations\PostParentPostRelation;
use Wpify\Model\Relations\PostTermsRelation;
use Wpify\Model\Relations\PostTopLevelParentPostRelation;
use Wpify\Model\User;

/**
 * Class AbstractPostModel
 * @package Wpify\Model
 *
 * @property PostRepositoryInterface $_repository
 */
abstract class AbstractPostModel extends AbstractModel implements PostModelInterface {
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
	 * @var int
	 */
	public $author_id = 0;

	/**
	 * Post author.
	 *
	 * @var User
	 */
	public $author;

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
	 * Parent post
	 *
	 * @var self
	 */
	public $parent;

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
	public $post_type;

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
	 * Post categories
	 *
	 * @var TermModelInterface[]
	 */
	public $categories;

	/**
	 * Post tags
	 *
	 * @var TermModelInterface[]
	 */
	public $tags;

	/**
	 * Children of the post
	 *
	 * @var self[]
	 */
	public $children;

	/**
	 * Top level parent
	 *
	 * @var self[]
	 */
	public $top_level_parent;



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
	);

	public function __construct( $object, PostRepositoryInterface $repository ) {
		parent::__construct( $object, $repository );
	}

	/**
	 * @return string
	 */
	static function meta_type(): string {
		return 'post';
	}

	/**
	 * @return PostRepositoryInterface
	 */
	public function model_repository(): PostRepositoryInterface {
		return $this->_repository;
	}


	protected function parent_relation(): PostParentPostRelation {
		return new PostParentPostRelation( $this, $this->model_repository() );
	}

	protected function after_parent_set() {
		$this->parent_id = $this->parent->id ?? null;
	}

	protected function after_parent_id_set() {
		unset( $this->parent );
	}

	protected function author_relation(): PostAuthorRelation {
		return new PostAuthorRelation( $this, $this->model_repository()->get_user_repository() );
	}

	protected function after_author_set() {
		$this->author_id = $this->author->id ?? null;
	}

	protected function after_author_id_set() {
		unset( $this->author );
	}

	protected function categories_relation(): PostTermsRelation {
		return new PostTermsRelation(
			$this,
			'categories',
			$this->model_repository()->get_category_repository(),
			$this->model_repository()
		);
	}

	protected function tags_relation(): PostTermsRelation {
		return new PostTermsRelation(
			$this,
			'tags',
			$this->model_repository()->get_post_tag_repository(),
			$this->model_repository()
		);
	}

	public function children_relation()
	{
		return new PostChildrenPostsRelation($this, $this->_repository);
	}

	public function top_level_parent_relation()
	{
		return new PostTopLevelParentPostRelation($this, $this->_repository);
	}
}
