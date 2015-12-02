<?php

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctRequest.php');

/**
 * Class xoctSecureLink
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctSecureLink {

	/**
	 * @param $url
	 *
	 * @return mixed
	 */
	public static function sign($url) {
		if (! $url) {
			return '';
		}

		$data = json_decode(xoctRequest::root()->security()->sign($url));

		if ($data->error) {
			return '';
		}

		return $data->url;
	}
}

?>
