<?php

namespace WpifyModel;

/**
 * Class BasicPage
 * @package WpifyModel
 */
class Page extends AbstractPostModel {
	/**
	 * @return string
	 */
	static function post_type(): string {
		return 'page';
	}
}
