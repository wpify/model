<?php

namespace WpifyModel\Interfaces;

interface ModelInterface {
	public function refresh( $object = null );

	public function model_repository();
}
