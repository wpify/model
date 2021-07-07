<?php

namespace WpifyModel;

interface ModelInterface {
	public function __construct( $object, $relations );

	public function save();

	public function refresh( $object = null );

	public function get_meta( $key );

	public function set_meta( $key, $value );
}
