<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

#[Attribute( Attribute::TARGET_PROPERTY )]
class TermPostsRelation implements SourceAttributeInterface {
	/**
	 * @param class-string $target_entity
	 */
	public function __construct( public string $target_entity ) {
	}

	public function get( ModelInterface $model, string $key ): mixed {
		$manager    = $model->manager();
		$repository = $manager->get_model_repository( $this->target_entity );

		return $repository->find_all_by_term( $model );
	}
}
