<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Support;

use Wpify\Model\Attributes\AccessorObject;
use Wpify\Model\Attributes\AliasOf;
use Wpify\Model\Attributes\ChildPostsRelation;
use Wpify\Model\Attributes\ChildTermsRelation;
use Wpify\Model\Attributes\IdsRelation;
use Wpify\Model\Attributes\ManyToOneRelation;
use Wpify\Model\Attributes\SourceObject;
use Wpify\Model\Model;
use Wpify\Model\Post;

/**
 * Probe model exercising attribute behaviors that no shipped model triggers:
 * failure modes of the relation attributes, AccessorObject, AliasOf,
 * IdsRelation, and dotted SourceObject paths.
 */
class RelationProbeModel extends Model {
	public int $id = 0;

	public int $ref_id = 0;

	/** Untyped on purpose: ManyToOneRelation must reject it. */
	#[ManyToOneRelation( 'ref_id' )]
	public $ref = null;

	/** The repository lacks find_child_posts_of(). */
	#[ChildPostsRelation]
	public array $child_posts = array();

	/** The repository lacks find_child_terms_of(). */
	#[ChildTermsRelation]
	public array $child_terms = array();

	#[AccessorObject]
	public string $acc = '';

	#[AliasOf( 'ref_id' )]
	public int $ref_alias = 0;

	#[SourceObject( 'nested.value' )]
	public string $nested_value = '';

	public array $ids = array();

	/** @var Post[] */
	#[IdsRelation( 'ids', Post::class )]
	public array $posts_by_ids = array();
}
