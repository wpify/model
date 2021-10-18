<?php

namespace Wpify\Model\Abstracts;

use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\PostRepositoryInterface;

/**
 * Class AbstractPostModel
 * @package Wpify\Model
 *
 * @property PostRepositoryInterface $_repository
 */
abstract class AbstractDbModel extends AbstractModel implements ModelInterface {
	public function get_db_data() {
		$data = [];

		foreach (
			array_filter( $this->own_props(), function ( $prop ) {
				return $prop['source'] === 'object';
			} ) as $key => $item
		) {
			$data[ $key ] = $this->{$key};
		}

		return $data;
	}

	public function model_repository() {
		return $this->_repository;
	}

	static function meta_type() {
		return 'db';
	}
}
