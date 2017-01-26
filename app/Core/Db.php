<?php
    namespace Core;

    use RedBeanPHP\R;

    /**
     * Class Db
     *
     * @package Core
     */
    class Db
    {

        /**
         * Db constructor.
         */
        public static function init()
        {
            $config = App::$config;

            $dsn = "mysql:host={$config->db['hostname']};dbname={$config->db['database']}";
            $user = $config->db['username'];
            $pass = $config->db['password'];

            R::setup($dsn, $user, $pass);
        }
    }