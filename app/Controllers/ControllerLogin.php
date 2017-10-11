<?php

namespace Controllers;

use Models\Db;

/**
 * Class ControllerLogin.
 */
class ControllerLogin extends Controller
{
	/**
	 * @param array $options
	 */
	public function action_index(array $options = [])
	{
		$post = [
			'post_data' => $_POST,
		];
		$options = array_merge($options, $post);
		if (!Db::isAdminUserInstaled()) {
			$this->view_backend->render('init', $options);
		} else {
			$this->view_backend->render('login', $options);
		}
	}
}
