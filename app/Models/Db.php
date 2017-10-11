<?php

namespace Models;

use Core\App;
use RedBeanPHP\R;

/**
 * Class Db.
 */
class Db extends Model
{
	/**
	 * Db constructor.
	 */
	public static function init()
	{
		$config = App::$config;
		$dsn = "mysql:host={$config->db['hostname']};dbname={$config->db['database']}";
		$user = $config->db['username'];
		$pass = $config->db['password'];
		R::setup($dsn, $user, $pass);
	}

	public static function isAdminUserInstaled()
	{
		$user = R::findOne('users', 'role = ?', ['admin']);

		return !(null === $user);
	}
}
