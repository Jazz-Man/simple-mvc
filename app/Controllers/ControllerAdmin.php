<?php

    namespace Controller;


    use Core\Controller;

    /**
     * Class ControllerAdmin
     *
     * @package Controller
     */
    class ControllerAdmin extends Controller
    {
        public function action_index()
        {
            $options = [];
            $this->view->generate('admin', $options);
        }
    }
