<?php

namespace Controllers;

use Models\Post;

/**
 * Class ControllerHome.
 */
class ControllerSinglePost extends Controller
{
	public function action_index()
	{
		$post = [
			'post_data' => Post::getPost($_GET['id']),
		];
		$this->view_frontend->render('single-post', $post);
	}
}
