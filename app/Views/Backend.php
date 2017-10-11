<?php
namespace Views;

/**
	 * Class Backend.
	 */
	class Backend extends View
	{
		/**
		 * @param string     $content_view
		 * @param array|null $data
		 */
		public function render(string $content_view, array $data = null)
		{
			self::generate("@backend/$content_view", $data);
		}
	}
