<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Order;
use Wpify\Model\OrderItem;
use Wpify\Model\Product;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Comment;
use Wpify\Model\Interfaces\SourceAttributeInterface;
use Wpify\Model\Post;
use Wpify\Model\Site;
use Wpify\Model\Term;
use Wpify\Model\User;

#[Attribute( Attribute::TARGET_PROPERTY )]
class Meta implements SourceAttributeInterface {
	public function __construct( public ?string $meta_key = null, public bool $single = true ) {
	}

	public function get( ModelInterface $model, string $key ): mixed {
		$meta_key = $this->meta_key ?? $key;

		return match ( $this->meta_type( $model ) ) {
			'post'    => get_post_meta( $model->id, $meta_key, $this->single ),
			'user'    => get_user_meta( $model->id, $meta_key, $this->single ),
			'term'    => get_term_meta( $model->id, $meta_key, $this->single ),
			'comment' => get_comment_meta( $model->id, $meta_key, $this->single ),
			'wc'      => $model->source()->get_meta( $meta_key, $this->single ),
			'site'    => $this->site_meta_supported() ? get_site_meta( $model->id, $meta_key, $this->single ) : null,
			default   => null,
		};
	}

	public function set( ModelInterface $model, string $key, mixed $value ): mixed {
		$meta_key = $this->meta_key ?? $key;

		return match ( $this->meta_type( $model ) ) {
			'wc'    => $model->source()->update_meta_data( $meta_key, $value ),
			'site'  => $this->site_meta_supported() ? update_site_meta( $model->id, $meta_key, $value ) : null,
			default => null,
		};
	}

	/**
	 * Maps the model to the meta storage it reads from and writes to.
	 */
	private function meta_type( ModelInterface $model ): ?string {
		return match ( true ) {
			$model instanceof Post => 'post',
			$model instanceof User => 'user',
			$model instanceof Term => 'term',
			$model instanceof Comment => 'comment',
			$model instanceof Product,
			$model instanceof OrderItem,
			$model instanceof Order => 'wc',
			$model instanceof Site => 'site',
			default => null,
		};
	}

	private function site_meta_supported(): bool {
		return function_exists( 'is_site_meta_supported' ) && is_site_meta_supported();
	}
}
