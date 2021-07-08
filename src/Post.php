<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractPostModel;

/**
 * Class BasicPost
 * @package WpifyModel
 */
class Post extends AbstractPostModel {
	/**
	 * @return string
	 */
	static function post_type(): string {
		return 'post';
	}
}
