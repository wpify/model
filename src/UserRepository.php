<?php

namespace WpifyModel;

use PmsDeps\Wpify\Core\Models\UserModel;
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
	static function model(): string {
		return UserModel::class;
	}
}
