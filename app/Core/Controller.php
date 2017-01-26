<?php
    namespace Core;

    /**
     * Class Controller
     *
     * @package Core
     */
    class Controller
    {
        public $model;
        public $view;

        /**
         * Controller constructor.
         */
        public function __construct()
        {
            $this->view = new View();
        }

    }