<?php

namespace WpifyModel\Interfaces;

interface ModelInterface {
	public function __construct( $object, $relations );

	public function refresh( $object = null );
}
