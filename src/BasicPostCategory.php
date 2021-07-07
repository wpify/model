<?php

namespace WpifyModel;

class BasicPostCategory extends AbstractTermModel {
	static function taxonomy(): string {
		return 'category';
	}
}
