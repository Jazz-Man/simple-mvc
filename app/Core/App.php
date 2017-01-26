<?php
    namespace Core;

    /**
     * Class App
     *
     * @package Core
     */
    class App
    {
        public static $config;
        public static $template;

        

        private static function templateSetup()
        {
            $loader = new \Twig_Loader_Filesystem(self::$config->views_dir);
            $twig = new \Twig_Environment($loader, self::$config->template_setup);

            self::$template = $twig;
        }

        /**
         * @param $config
         */
        public static function start($config)
        {
            self::$config = $config;

            Db::init();

            self::templateSetup();

            Url::init();
        }

    }