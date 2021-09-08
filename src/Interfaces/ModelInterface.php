<?php

namespace Wpify\Model\Interfaces;

interface ModelInterface {
	public function refresh( $object = null );

	public function model_repository();
}
