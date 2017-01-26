<?php
    namespace Models;

    use Core\App;
    use Core\Error;

    /**
     * Class ModelsPages
     *
     * @package Models
     */
    class ModelsPages
    {
        /**
         * @return array|\Core\Error
         */
        public static function getPages()
        {
            $all_pages = (array)App::$config->pages ?? [];
            $pages = [];
            if (empty($all_pages)) {
                $code    = 'pages_not_exists';
                $message = 'Жодних сторінок немає на Вашому сайті!.<br> Додайте їх в конфігураційний файл!';

                return new Error($code, $message);
            } else {
                foreach ($all_pages as $page => $option) {
                    $pages[$page] = [
                        'method'   => 'GET',
                        'url'      => $option['url'],
                        'callback' => "Controller\\Controller{$page}::action_index"
                    ];

                }

            }

            return $pages;
        }
    }
