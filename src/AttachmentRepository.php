<?php
declare( strict_types=1 );

namespace Wpify\Model;

/**
 * @method Attachment|null get( mixed $source )
 * @method Attachment create( array $data )
 * @method Attachment save( Attachment $model )
 * @method bool delete( Attachment $model, bool $force_delete = true )
 * @method Attachment[] find( array $args = [] )
 * @method Attachment[] find_all( array $args = array() )
 * @method Attachment[] find_published( array $args = array() )
 */
class AttachmentRepository extends PostRepository {
	/**
	 * Returns the model class name.
	 *
	 * @return string
	 */
	public function model(): string {
		return Attachment::class;
	}

	/**
	 * Returns post types for the repository.
	 *
	 * @return string[]
	 */
	public function post_types(): array {
		return array( 'attachment' );
	}
}
