<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

#[Attribute( Attribute::TARGET_PROPERTY )]
class TopLevelTermParentRelation implements SourceAttributeInterface {
	public function __construct() {
	}

	public function get( ModelInterface $model, string $key ): mixed {
		$manager    = $model->manager();
		$repository = $manager->get_model_repository( get_class( $model ) );
		$top_parent = null;
		$taxonomy   = $repository->taxonomy();

		if ( isset( $model->parent_id ) ) {
			$ancestors  = get_ancestors( $model->id, $taxonomy, 'taxonomy' );
			$top_parent = end( $ancestors );
		}

		return $top_parent ? $repository->get( $top_parent ) : null;
	}
}
