<?php
/**
 * Class xoctRequestDummy
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctRequestDummy extends xoctRequest{

	public function get($as_user = '') {
		$var = parent::get($as_user);

		return $var;
	}
}