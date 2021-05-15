<?php

namespace WpifyModel;

/**
 * Class BasePostRepository
 * @package WpifyModel
 * @method BasicPost get( $object = null )
 * @method BasicPost[] find( array $args = array() )
 */
final class BasicPostRepository extends AbstractPostRepository {
	/**
	 * @param $object
	 *
	 * @return BasicPost
	 */
	protected function factory( $object ): BasicPost {
		return new BasicPost( $object );
	}
}
