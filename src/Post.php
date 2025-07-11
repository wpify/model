<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\ChildPostsRelation;
use Wpify\Model\Attributes\Meta;
use Wpify\Model\Attributes\PostTermsRelation;
use Wpify\Model\Attributes\SourceObject;
use Wpify\Model\Attributes\ReadOnlyProperty;
use Wpify\Model\Attributes\ManyToOneRelation;
use Wpify\Model\Attributes\TopLevelPostParentRelation;

class Post extends Model {
	/**
	 * Post ID.
	 */
	#[SourceObject( 'ID' )]
	public int $id = 0;

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 */
	#[SourceObject( 'post_author' )]
	public int $author_id = 0;

	/**
	 * The post's author.
	 */
	#[ReadOnlyProperty]
	#[ManyToOneRelation( 'author_id' )]
	public ?User $author = null;

	/**
	 * The post's local publication time.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public string $post_date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public string $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post's content.
	 */
	#[SourceObject( 'post_content' )]
	public string $content = '';

	/**
	 * The post's title.
	 */
	#[SourceObject( 'post_title' )]
	public string $title = '';

	/**
	 * The post's excerpt.
	 */
	#[SourceObject( 'post_excerpt' )]
	public string $excerpt = '';

	public string $rendered_excerpt = '';

	/**
	 * The post's status.
	 */
	#[SourceObject]
	public string $post_status = 'publish';

	/**
	 * Whether comments are allowed.
	 */
	#[SourceObject]
	public string $comment_status = 'open';

	/**
	 * Whether pings are allowed.
	 */
	#[SourceObject]
	public string $ping_status = 'open';

	/**
	 * The post's password in plain text.
	 */
	#[SourceObject( 'post_password' )]
	public string $password = '';

	/**
	 * The post's slug.
	 */
	#[SourceObject( 'post_name' )]
	public string $slug = '';

	/**
	 * URLs queued to be pinged.
	 */
	#[SourceObject]
	public string $to_ping = '';

	/**
	 * URLs that have been pinged.
	 */
	#[SourceObject]
	public string $pinged = '';

	/**
	 * The post's local modified time.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public string $post_modified = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public string $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * A utility DB field for post content.
	 */
	#[SourceObject( 'post_content_filtered' )]
	#[ReadOnlyProperty]
	public string $content_filtered = '';

	/**
	 * ID of a post's parent post.
	 */
	#[SourceObject( 'post_parent' )]
	public $parent_id = 0;

	/**
	 * Parent post.
	 */
	#[ManyToOneRelation( 'parent_post_id' )]
	public ?Post $parent = null;

	/**
	 * Top Parent post.
	 */
	#[TopLevelPostParentRelation]
	public ?Post $top_parent = null;

	/**
	 * Posts that are children of this post.
	 *
	 * @var Post[]
	 */
	#[ChildPostsRelation]
	public array $children = array();

	/**
	 * The unique identifier for a post, not necessarily a URL, used as the feed GUID.
	 */
	#[SourceObject]
	public string $guid = '';

	/**
	 * A field used for ordering posts.
	 */
	#[SourceObject]
	public int $menu_order = 0;

	/**
	 * The post's type, like post or page.
	 */
	#[SourceObject]
	public string $post_type = 'post';

	/**
	 * An attachment's mime type.
	 */
	#[SourceObject( 'post_mime_type' )]
	public string $mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public int $comment_count = 0;

	/**
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 */
	#[SourceObject]
	#[ReadOnlyProperty]
	public string $filter = '';

	/**
	 * The post's permalink.
	 */
	#[ReadOnlyProperty]
	public string $permalink = '';

	/**
	 * Featured image ID.
	 */
	public int $featured_image_id = 0;

	#[ReadOnlyProperty]
	#[ManyToOneRelation( 'featured_image_id' )]
	public ?Attachment $featured_image = null;

	/**
	 * The post's page template.
	 */
	#[Meta( '_wp_page_template' )]
	public string $page_template = '';

	/**
	 * The post's categories.
	 *
	 * @var Category[]
	 */
	#[PostTermsRelation( target_entity: Category::class )]
	public array $categories = array();

	/**
	 * The post's tags.
	 *
	 * @var PostTag[]
	 */
	#[PostTermsRelation( target_entity: PostTag::class )]
	public array $tags = array();

	/**
	 * The post's permalink.
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		return get_permalink( $this->id );
	}

	/**
	 * The post's featured image ID.
	 *
	 * @return int
	 */
	public function get_featured_image_id(): int {
		return intval( get_post_thumbnail_id( $this->id ) );
	}

	/**
	 * Set the post's featured image ID.
	 *
	 * @param int $featured_image_id
	 */
	public function persist_featured_image_id( int $featured_image_id ): void {
		set_post_thumbnail( $this->id, $featured_image_id );
	}

	public function get_rendered_excerpt() {
		return get_the_excerpt( $this->id );
	}
}
