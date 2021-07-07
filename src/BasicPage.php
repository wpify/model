<?php

namespace WpifyModel;

/**
 * Class BasicPage
 * @package WpifyModel
 */
final class BasicPage extends AbstractPostModel {
	/**
	 * @return string
	 */
	static function post_type(): string {
		return 'page';
	}
}
