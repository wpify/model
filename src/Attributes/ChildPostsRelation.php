<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Exceptions\RepositoryMethodNotImplementedException;
use Wpify\Model\Exceptions\RepositoryNotFoundException;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;
use Wpify\Model\Post;

#[Attribute( Attribute::TARGET_PROPERTY )]
class ChildPostsRelation implements SourceAttributeInterface {
	/**
	 * Gets child posts of given model.
	 *
	 * @param ModelInterface $model
	 * @param string $key
	 *
	 * @return Post
	 * @throws RepositoryMethodNotImplementedException
	 * @throws RepositoryNotFoundException
	 */
	public function get( ModelInterface $model, string $key ): mixed {
		$manager    = $model->manager();
		$repository = $manager->get_model_repository( get_class( $model ) );

		if (method_exists($repository, 'find_child_posts_of')) {
			return $repository->find_child_posts_of( $model );
		}

		throw new RepositoryMethodNotImplementedException( 'Repository method find_child_posts_of is not implemented in ' . get_class( $repository ) );
	}
}
