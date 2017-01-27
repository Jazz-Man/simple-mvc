<?php
    namespace Controllers;

    use Models\Login;
    use Models\Route;

    /**
     * Class ControllerLogout
     *
     * @package Controllers
     */
    class ControllerLogout extends Controller
    {
        public function action_index()
        {
            Login::logout();
            Route::redirectHome();
        }

    }