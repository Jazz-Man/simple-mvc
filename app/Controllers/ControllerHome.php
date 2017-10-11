<?php

namespace Controllers;

use Models\Post;

/**
 * Class ControllerHome.
 */
class ControllerHome extends Controller
{
	public function action_index()
	{
		$posts = Post::getPosts();
		$options = [
			'posts' => $posts,
		];
		$this->view_frontend->render('index', $options);
	}
}
