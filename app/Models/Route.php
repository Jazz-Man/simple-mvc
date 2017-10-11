<?php

namespace Models;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * Class Route.
 */
class Route extends Model
{
	public static function init()
	{
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		$uri = $_SERVER['REQUEST_URI'];
		//            var_dump($_SERVER['QUERY_STRING']);
		$dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
			$pages = Pages::getPages();
			if (!Error::isErrors($pages)) {
				foreach ((array) $pages as $page) {
					$r->addRoute($page['method'], $page['url'], $page['callback']);
				}
			} else {
				echo $pages->getErrorMessage();
				die();
			}
			// Admin
			$r->get('/admin/add-post', 'Controllers\\ControllerAdminEditPost::action_index');
			$r->post('/admin/add_post', 'Models\\Post::add');
			$r->post('/admin/edit_post', 'Models\\Post::edit');
			$r->post('/admin/delete_post', 'Models\\Post::delete');
			$r->get('/admin/{path:.*}', 'Controllers\\ControllerAdmin::action_index');
			$r->post('/login', 'Models\\Login::auch');
			$r->post('/logout', 'Controllers\\ControllerLogout::action_index');
			$r->get('/post/{path:.*}', 'Controllers\\ControllerSinglePost::action_index');
			// 404 page
			$r->get('/{path:.*}', 'Controllers\\Controller404::action_index');
		});
		if (false !== $pos = mb_strpos($uri, '?')) {
			$uri = mb_substr($uri, 0, $pos);
		}
		$uri = rawurldecode($uri);
		$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
		switch ($routeInfo[0]) {
			case Dispatcher::NOT_FOUND:
				self::pageRedirect('404');
				break;
			case Dispatcher::METHOD_NOT_ALLOWED:
				$allowedMethods = $routeInfo[1];
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
	 * @param     $location
	 * @param int $status
	 */
	public static function pageRedirect($location, $status = 302)
	{
		$redirect_url = self::siteUrl().DIRECTORY_SEPARATOR.$location;
		self::redirect($redirect_url, $status);
	}

	/**
	 * @return string
	 */
	public static function siteUrl()
	{
		$server_host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
		$server_protocol = 0 === mb_stripos($_SERVER['SERVER_PROTOCOL'], 'https') ? 'https://' : 'http://';

		return $server_protocol.$server_host;
	}

	/**
	 * @param     $location
	 * @param int $status
	 *
	 * @return bool
	 */
	public static function redirect($location, $status = 302)
	{
		if (PHP_SAPI !== 'cgi-fcgi') {
			self::status_header($status);
		}
		header("Location: $location", true, $status);
		exit;
	}

	/**
	 * @param        $code
	 * @param string $description
	 */
	public static function status_header($code, $description = '')
	{
		if (!$description) {
			$description = self::status_header_desc($code);
		}
		if (empty($description)) {
			return;
		}
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$status_header = "$protocol $code $description";
		@header($status_header, true, $code);
	}

	/**
	 * @param $code
	 *
	 * @return mixed|string
	 */
	public static function status_header_desc($code)
	{
		$code = (int) $code;
		$desc = [
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			421 => 'Misdirected Request',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			451 => 'Unavailable For Legal Reasons',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended',
			511 => 'Network Authentication Required',
		];
		if (isset($desc[$code])) {
			return $desc[$code];
		}

		return '';
	}

	/**
	 * @param $handler
	 *
	 * @return array|\Models\Error
	 */
	public static function controllerExist($handler)
	{
		list($class, $method) = explode('::', $handler);
		if (class_exists($class) && method_exists($class, $method)) {
			return [
				'class' => $class,
				'method' => $method,
			];
		}
		$code = 'class_and_method_not_exists';
		$message = "Немає такого класу <strong>{$class}</strong> з таким методом <strong>{$method}</strong>";

		return new Error($code, $message, $handler);
	}

	public static function redirectHome()
	{
		self::pageRedirect('');
	}
}
