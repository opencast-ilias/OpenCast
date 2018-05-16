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
	 * @var bool
	 */
	protected static $init_js = false;


	/**
	 *
	 */
	public static function loadLib() {
		global $DIC;
		$tpl = $DIC['tpl'];
		if (!self::$init) {
			$tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.min.js');
			$tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.css');
			self::$init = true;
		}
	}


	/**
	 * @param string $type
	 */
	public static function initJS($type = 'waiter') {
		self::loadLib();
		if (!self::$init_js) {
			global $DIC;
			$tpl = $DIC['tpl'];
			$code = 'xoctWaiter.init(\'' . $type . '\');';
			$tpl->addOnLoadCode($code);
			self::$init_js = true;
		}
	}


	/**
	 * @param $dom_selector_string
	 */
	public static function addListener($dom_selector_string) {
		global $DIC;
		$tpl = $DIC['tpl'];
		$code = 'xoctWaiter.addListener("' . $dom_selector_string . '");';
		$tpl->addOnLoadCode($code);
	}


	/**
	 * @param $dom_selector_string
	 */
	public static function addLinkOverlay($dom_selector_string) {
		global $DIC;
		$tpl = $DIC['tpl'];
		$code = 'xoctWaiter.addLinkOverlay("' . $dom_selector_string . '");';
		$tpl->addOnLoadCode($code);
	}


	public static function show() {
		global $DIC;
		$tpl = $DIC['tpl'];
		self::initJS();
		$code = 'xoctWaiter.show();';
		$tpl->addOnLoadCode($code);
	}
}

?>
