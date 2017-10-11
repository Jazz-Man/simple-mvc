<?php

namespace Models;

class User
{
	public static function getCurentUser()
	{
		$sessionvar = Login::getLoginSessionVar();
		if (!isset($_SESSION[$sessionvar])) {
			$code = 'user_not_exists';
			$message = 'User not found';

			return new Error($code, $message);
		}

		return $_SESSION[$sessionvar];
	}
}
