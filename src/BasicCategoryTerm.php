<?php

namespace WpifyModel;

final class BasicCategoryTerm extends AbstractTermModel {
	protected function taxonomy(): string {
		return 'category';
	}
}
