<?php

namespace Models;

use Core\App;

/**
 * Class Pages.
 */
class Pages extends Model
{
	/**
	 * @return array|\Models\Error
	 */
	public static function getPages()
	{
		$all_pages = App::$config->pages ?? [];
		$pages = [];
		if (empty($all_pages)) {
			$code = 'pages_not_exists';
			$message = 'Жодних сторінок немає на Вашому сайті!.<br> Додайте їх в конфігураційний файл!';

			return new Error($code, $message);
		}
		foreach ($all_pages as $page => $option) {
			$pages[$page] = [
					'method' => 'GET',
					'url' => $option['url'],
					'callback' => "Controllers\\Controller{$page}::action_index",
				];
		}

		return $pages;
	}
}
