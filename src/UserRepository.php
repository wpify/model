<?php

namespace WpifyModel;

use WpifyModel\Abstracts\AbstractPostRepository;
use WpifyModel\Abstracts\AbstractUserRepository;

/**
 * Class BasePostRepository
 * @package WpifyModel
 *
 * @method User[] all()
 * @method User[] find( array $args = array() )
 * @method User create()
 * @method User get( $object = null )
 * @method mixed save( $model )
 * @method mixed delete( $model )
 */
class UserRepository extends AbstractUserRepository {
	public function model(): string {
		return User::class;
	}
}
