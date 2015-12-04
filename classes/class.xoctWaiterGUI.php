<?php

/**
 * Class xoctWaiterGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctWaiterGUI {

	/**
	 * @var bool
	 */
	protected static $init = false;


	/**
	 *
	 */
	public static function loadLib() {
		global $tpl;
		if (!self::$init) {
			$tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.js');
			$tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.css');
			self::$init = true;
		}
	}


	public static function initJS() {
		self::loadLib();
		global $tpl;
		$code = 'xoctWaiter.init();';
		$tpl->addOnLoadCode($code);
	}


	/**
	 * @param $dom_selector_string
	 */
	public static function addListener($dom_selector_string) {
		global $tpl;
		$code = 'xoctWaiter.addListener("' . $dom_selector_string . '");';
		$tpl->addOnLoadCode($code);
	}

	/**
	 * @param $dom_selector_string
	 */
	public static function addLinkOverlay($dom_selector_string) {
		global $tpl;
		$code = 'xoctWaiter.addLinkOverlay("' . $dom_selector_string . '");';
		$tpl->addOnLoadCode($code);
	}
}

?>
