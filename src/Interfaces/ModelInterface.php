<?php

namespace WpifyModel\Interfaces;

interface ModelInterface {
	public function __construct( $object, $relations );

	public function refresh( $object = null );

	public function get_meta( $key );

	public function set_meta( $key, $value );
}
