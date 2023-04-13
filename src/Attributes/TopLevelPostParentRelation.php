<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

#[Attribute( Attribute::TARGET_PROPERTY )]
class TopLevelPostParentRelation implements SourceAttributeInterface {
	public function __construct() {
	}

	public function get( ModelInterface $model, string $key ): mixed {
		$manager    = $model->manager();
		$repository = $manager->get_model_repository( get_class( $model ) );
		$top_parent = null;
		$post_types = $repository->post_types();

		if ( count( $post_types ) !== 1 ) {
			return null;
		}

		$post_type = $post_types[0];

		if ( isset( $model->parent_id ) ) {
			$ancestors  = get_ancestors( $model->id, $post_type, 'post_type' );
			$top_parent = end( $ancestors );
		}

		return $top_parent ? $repository->get( $top_parent ) : null;
	}
}
