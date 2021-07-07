<?php

namespace WpifyModel;

class Category extends AbstractTermModel {
	static function taxonomy(): string {
		return 'category';
	}
}
