<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractTermModel;

class PostTag extends AbstractTermModel {
	static function taxonomy(): string {
		return 'post_tag';
	}
}
