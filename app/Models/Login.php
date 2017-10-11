<?php

namespace Models;

use Controllers\ControllerLogin;
use RedBeanPHP\R;

/**
 * Class Login.
 */
class Login extends Model
{
	/**
	 * @return bool|string
	 */
	public function auch()
	{
		if (empty($_POST)) {
			return '';
		}
		switch ($_POST['login']) {
			case 'init':
				self::initProcessing($_POST);
				break;
			case 'login':
				self::loginProcessing($_POST);
				break;
			default:
				return;
		}
	}

	/**
	 * @param $data
	 */
	public static function initProcessing($data)
	{
		$validation = new DataValidator();
		$data = $validation->sanitize($data);
		$validation->validation_rules([
				'username' => 'required|alpha_numeric|max_len,60|min_len,1',
				'password' => 'required|max_len,60|min_len,6',
			]);
		$validation->filter_rules([
				'username' => 'trim|sanitize_string',
				'password' => 'trim',
			]);
		$validated_data = $validation->run($data);
		if ($validated_data === false) {
			$options = [
				'auch_validation' => $validation->get_readable_errors(),
			];
			$login = new ControllerLogin();
			$login->action_index($options);
		} else {
			$user = R::dispense('users');
			$user->username = $validated_data['username'];
			$user->password = password_hash($validated_data['password'], PASSWORD_DEFAULT);
			$user->role = 'admin';
			R::store($user);
			$_SESSION[static::getLoginSessionVar()] = $validated_data['username'];
			Route::pageRedirect('admin');
		}
	}

	/**
	 * @return string
	 */
	public static function getLoginSessionVar()
	{
		return 'usr_'.session_id();
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public static function loginProcessing($data)
	{
		$validation = new DataValidator();
		$data = $validation->sanitize($data);
		$validation->validation_rules([
				'username' => 'required|alpha_numeric|max_len,60|min_len,1',
				'password' => 'required|max_len,60|min_len,6',
			]);
		$validation->filter_rules([
				'username' => 'trim|sanitize_string',
				'password' => 'trim',
			]);
		$validated_data = $validation->run($data);
		$login_view = new ControllerLogin();
		$login_view_options = [];
		// Валідація даних пройшла
		if ($validated_data === false) {
			$login_view_options['auch_validation'][] = $validation->get_readable_errors();
			$login_view->action_index($login_view_options);
		} else {
			$user = R::findOne('users', 'username = ?', [$validated_data['username']]);
			if (null !== $user) {
				if (password_verify($validated_data['password'], $user->password)) {
					$_SESSION[static::getLoginSessionVar()] = $validated_data['username'];
					Route::pageRedirect('admin');
				} else {
					$login_view_options['auch_validation'][] = 'Пароль введено невірно!';
					$login_view->action_index($login_view_options);
				}
			} else {
				$login_view_options['auch_validation'][]
					= "Користувач з таким логiном <strong>{$validated_data['username']}</strong> не знайдений !";
				$login_view->action_index($login_view_options);
			}
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function checkLogin()
	{
		$sessionvar = self::getLoginSessionVar();
		if (empty($_SESSION[$sessionvar])) {
			return false;
		}

		return true;
	}

	public static function logout()
	{
		$sessionvar = self::getLoginSessionVar();
		$_SESSION[$sessionvar] = null;
		unset($_SESSION[$sessionvar]);
	}
}
