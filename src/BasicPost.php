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
	protected function post_type(): string {
		return 'post';
	}
}
