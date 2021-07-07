<?php

namespace WpifyModel;

/**
 * Class BasicPost
 * @package WpifyModel
 */
final class BasicPost extends AbstractPostModel {
	/**
	 * @return string
	 */
	static function post_type(): string {
		return 'post';
	}
}
