<?php

namespace Controllers;

/**
 * Class Controller404.
 */
class Controller404 extends Controller
{
	public function action_index()
	{
		$options = [];
		$this->view_frontend->render('404', $options);
	}
}
