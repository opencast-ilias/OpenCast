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


	/**
	 * @param $url
	 *
	 * @return mixed
	 * @throws xoctException
	 */
	public static function signThumbnail($url) {
		$duration = xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS_TIME);
		$valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
		return self::sign($url, $valid_until, xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS_WITH_IP));
	}


	/**
	 * @param $url
	 *
	 * @return mixed
	 * @throws xoctException
	 */
	public static function signAnnotation($url) {
		$duration = xoctConf::getConfig(xoctConf::F_SIGN_ANNOTATION_LINKS_TIME);
		$valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
		return self::sign($url, $valid_until, xoctConf::getConfig(xoctConf::F_SIGN_ANNOTATION_LINKS_WITH_IP));
	}


	/**
	 * @param   $url
	 *
	 * @param 0 $duration
	 *
	 * @return mixed
	 * @throws xoctException
	 */
	public static function signPlayer($url, $duration = 0) {
		$valid_until = null;
		if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT) && $duration > 0) {
			$duration_in_seconds = $duration / 1000;
			$additional_time_percent = xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT) / 100;
			$valid_until = gmdate("Y-m-d\TH:i:s\Z", time() + $duration_in_seconds + $duration_in_seconds * $additional_time_percent);
		}
		return self::sign($url, $valid_until, xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS_WITH_IP));
	}


	/**
	 * @param $url
	 *
	 * @return mixed
	 * @throws xoctException
	 */
	public static function signDownload($url) {
		$duration = xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS_TIME);
		$valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
		return self::sign($url, $valid_until, xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS_WITH_IP));
	}

}

?>
