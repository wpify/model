<?php

namespace WpifyModel;

final class BasicCategoryTerm extends AbstractTermModel {
	static function taxonomy(): string {
		return 'category';
	}
}
