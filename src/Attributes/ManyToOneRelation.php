<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Exceptions\RepositoryNotFoundException;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

#[Attribute( Attribute::TARGET_PROPERTY )]
class ManyToOneRelation implements SourceAttributeInterface {
	public function __construct( public string $source_key ) {
	}

	public function get( ModelInterface $model, string $key ): mixed {
		$manager   = $model->manager();
		$reflexion = $model->reflection();
		$property  = $reflexion->getProperty( $key );
		$type      = $property->getType();

		if ( ! $type || ! class_exists( $type->getName() ) ) {
			throw new RepositoryNotFoundException( 'Unable to find repository for ' . get_class( $model ) . ':' . $key );
		}

		$repository = $manager->get_model_repository( $type->getName() );

		if ( ! empty( $model->{$this->source_key} ) ) {
			return $repository->get( $model->{$this->source_key} );
		}

		return null;
	}
}
