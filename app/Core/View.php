<?php
    namespace Core;

    /**
     * Class View
     *
     * @package Core
     */
    class View
    {
        /**
         * @param            $content_view
         * @param array|null $data
         */
        public function generate($content_view, array $data = null)
        {
            $template_engin = App::$template;

            echo $template_engin->render($content_view.'.html', $data);
        }

    }