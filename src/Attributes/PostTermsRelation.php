<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;
use Wpify\Model\PostRepository;

#[Attribute( Attribute::TARGET_PROPERTY )]
class PostTermsRelation implements SourceAttributeInterface {
	/**
	 * @param class-string $target_entity
	 */
	public function __construct( public string $target_entity ) {
	}

	public function get( ModelInterface $model, ?string $key = null ): mixed {
		$manager    = $model->manager();
		$repository = $manager->get_model_repository( $this->target_entity );

		return $repository->find_terms_of_post( $model->id );
	}

	public function persist( ModelInterface $post, string $key, array $terms ): void {
		$manager = $post->manager();

		/** @var PostRepository $repository */
		$repository = $manager->get_model_repository( $this->target_entity );

		$repository->assign_post_to_term( $post, $terms );
	}
}
