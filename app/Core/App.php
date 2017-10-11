<?php

namespace Core;

use Models\Db;
use Models\Route;

/**
 * Class App.
 */
class App
{
	public static $config;

	/**
	 * @param $config
	 */
	public static function start($config)
	{
		self::sessionStart();
		self::$config = $config;
		Db::init();
		Route::init();
	}

	public static function sessionStart()
	{
		ini_set('session.save_handler', 'files');
		session_save_path(sys_get_temp_dir());
		session_start();
	}
}
