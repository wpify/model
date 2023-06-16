<?php
declare( strict_types=1 );

namespace Wpify\Model\Exceptions;

use Exception;
use WP_Error;

class ModelException extends Exception {

	/**
	 * @var WP_Error|null
	 */
	private ?WP_Error $wp_error;

	public function __construct( $message = '', $code = 0, $wp_error = null ) {
		parent::__construct( $message, $code );
		$this->wp_error = $wp_error;
	}

	public function get_wp_error() {
		return $this->wp_error;
	}
}
