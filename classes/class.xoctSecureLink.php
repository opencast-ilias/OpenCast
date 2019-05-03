<?php

/**
 * Class xoctSecureLink
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctSecureLink {

	/**
	 * @var array
	 */
	protected static $cache = array();


	/**
	 * @param      $url
	 *
	 * @param null $valid_until
	 *
	 * @return mixed
	 * @throws xoctException
	 */
	public static function sign($url, $valid_until = null) {
		if (!$url) {
			return '';
		}
		if (isset(self::$cache[$url])) {
			return self::$cache[$url];
		}

		$data = json_decode(xoctRequest::root()->security()->sign($url, $valid_until));

		if ($data->error) {
			return '';
		}
		self::$cache[$url] = $data->url;

		return $data->url;
	}

}

?>
