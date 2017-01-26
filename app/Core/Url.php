<?php
    namespace Core;

    use FastRoute\Dispatcher;
    use FastRoute\RouteCollector;
    use Models\ModelsPages;

    /**
     * Class Url
     *
     * @package Core
     */
    class Url
    {
        public static function init()
        {
            $httpMethod = $_SERVER['REQUEST_METHOD'];
            $uri        = $_SERVER['REQUEST_URI'];
            $dispatcher = \FastRoute\simpleDispatcher(
                function (RouteCollector $r) {

                    $pages = ModelsPages::getPages();
                    if(!Error::isErrors($pages)){
                        foreach ((array)$pages as $page){
                            $r->addRoute($page['method'], $page['url'], $page['callback']);
                        }
                    }else{
                        echo $pages->getErrorMessage();
                        die();
                    }
                }
            );
            if (false !== $pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }
            $uri       = rawurldecode($uri);
            $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    var_dump('404 Not Found');
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    $allowedMethods = $routeInfo[1];
                    var_dump($allowedMethods);
                    break;
                case Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $class = self::controllerExist($handler);
                    if (Error::isErrors($class)) {
                        echo $class->getErrorMessage();
                    } else {
                        $obj = new $class['class']();
                        $obj->{$class['method']}();
                    }
                    break;
            }

        }

        /**
         * @param $handler
         *
         * @return array|\Core\Error
         */
        public static function controllerExist($handler)
        {
            list($class, $method) = explode('::', $handler);
            if (class_exists($class) && method_exists($class, $method)) {
                return [
                    'class'  => $class,
                    'method' => $method
                ];
            } else {
                $code    = 'class_and_method_not_exists';
                $message = "Немає такого класу <strong>{$class}</strong> з таким методом <strong>{$method}</strong>";

                return new Error($code, $message, $handler);
            }
        }
    }
