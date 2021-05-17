<?php

namespace WpifyModel;

/**
 * Class BasePageRepository
 * @package WpifyModel
 * @method BasicPage get( $object = null )
 * @method BasicPage[] find( array $args = array() )
 */
final class BasicPageRepository extends AbstractPostRepository {
	/**
	 * @param $object
	 *
	 * @return BasicPage
	 */
	protected function factory( $object ): BasicPage {
		return new BasicPage( $object );
	}

	protected function post_type(): string {
		return 'page';
	}
}
