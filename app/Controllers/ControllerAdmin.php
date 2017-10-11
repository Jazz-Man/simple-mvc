<?php

namespace Controllers;

use Models\Login;
use Models\Post;
use Models\Route;

/**
 * Class ControllerAdmin.
 */
class ControllerAdmin extends Controller
{
	public function action_index()
	{
		if (!Login::checkLogin()) {
			Route::pageRedirect('login');
		} else {
			$posts = Post::getPosts();
			$options = [
				'posts' => $posts,
			];
			if (isset($_GET['action'])) {
				switch ($_GET['action']) {
					case 'edit':
						$edit = new ControllerAdminEditPost();
						$edit->editPost($_GET['post']);
						break;
					case 'delete':
						$delete = new ControllerAdminEditPost();
						$delete->deletePost($_GET['post']);
						break;
				}
			} else {
				$this->view_backend->render('index', $options);
			}

			//                $this->view_backend->render('index', $options);
		}
	}
}
