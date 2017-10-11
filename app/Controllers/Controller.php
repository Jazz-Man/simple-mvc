<?php

namespace Controllers;

use Views\Backend;
use Views\Frontend;

/**
 * Class Controller.
 */
abstract class Controller
{
	public $view_frontend;
	public $view_backend;

	/**
	 * Controller constructor.
	 */
	public function __construct()
	{
		$this->view_frontend = new Frontend();
		$this->view_backend = new Backend();
	}
}
