<?php
namespace Views;

/**
	 * Class Frontend.
	 */
	class Frontend extends View
	{
		/**
		 * @param string     $content_view
		 * @param array|null $data
		 */
		public function render(string $content_view, array $data = null)
		{
			self::generate("@frontend/$content_view", $data);
		}
	}
