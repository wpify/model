<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;
use Wpify\Model\PostRepository;

#[Attribute( Attribute::TARGET_PROPERTY )]
class PostTermRelation implements SourceAttributeInterface {
	/**
	 * @param class-string $target_entity
	 */
	public function __construct( public string $target_entity ) {
	}

	public function get( ModelInterface $model, ?string $key = null ): mixed {
		$manager           = $model->manager();
		$target_repository = $manager->get_model_repository( $this->target_entity );
		$terms             = $target_repository->find_terms_of_post( $model->id );

		return $terms[0] ?? null;
	}

	public function persist( ModelInterface $post, string $key, $term ): void {
		$manager = $post->manager();

		/** @var PostRepository $source_repository */
		$source_repository = $manager->get_model_repository( $post::class );

		if ( method_exists( $source_repository, 'assign_post_to_term' ) ) {
			$source_repository->assign_post_to_term( $post, array( $term ) );
		}
	}
}
