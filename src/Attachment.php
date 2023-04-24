<?php
declare( strict_types=1 );

namespace Wpify\Model;

use Wpify\Model\Attributes\Meta;

class Attachment extends Post {
	/**
	 * Attached file relative path.
	 */
	#[Meta( '_wp_attached_file' )]
	public string $file;

	/**
	 * Metadata for the attachment.
	 */
	#[Meta( '_wp_attachment_metadata' )]
	public ?array $metadata;

	/**
	 * Alternative text for the image.
	 */
	#[Meta( '_wp_attachment_image_alt' )]
	public ?string $alt;
}
