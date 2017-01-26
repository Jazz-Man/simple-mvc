<?php
    
    namespace Controller;

    use Core\Controller;

    /**
     * Class ControllerHome
     *
     * @package Controller
     */
    class ControllerHome extends Controller
    {

        public function action_index()
        {
            $options = [];
            $this->view->generate('index', $options);
        }
    }