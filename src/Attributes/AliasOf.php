<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

/**
 * Alias attribute.
 *
 * The property os an alias of another property.
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class AliasOf implements SourceAttributeInterface {
	/**
	 * Alias constructor.
	 *
	 * @param string $source_key The aliased property.
	 */
	public function __construct( public string $source_key ) {
	}

	/**
	 * Get the aliased property.
	 *
	 * @param ModelInterface $model
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( ModelInterface $model, string $key ): mixed {
		return $model->{$this->source_key};
	}
}
