<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermModel;

class Category extends AbstractTermModel {
	static function taxonomy(): string {
		return 'category';
	}
}
