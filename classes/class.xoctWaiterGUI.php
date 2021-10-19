<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * Class xoctWaiterGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctWaiterGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

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
		if (!self::$init) {
			self::dic()->ui()->mainTemplate()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.min.js');
			self::dic()->ui()->mainTemplate()->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.css');
			self::$init = true;
		}
	}


	/**
	 * @param string $type
	 */
	public static function initJS($type = 'waiter') {
		self::loadLib();
		if (!self::$init_js) {
			$code = 'xoctWaiter.init(\'' . $type . '\');';
			self::dic()->ui()->mainTemplate()->addOnLoadCode($code);
			self::$init_js = true;
		}
	}


	/**
	 * @param $dom_selector_string
	 */
	public static function addListener($dom_selector_string) {
		$code = 'xoctWaiter.addListener("' . $dom_selector_string . '");';
		self::dic()->ui()->mainTemplate()->addOnLoadCode($code);
	}


	/**
	 * @param $dom_selector_string
	 */
	public static function addLinkOverlay($dom_selector_string) {
		$code = 'xoctWaiter.addLinkOverlay("' . $dom_selector_string . '");';
		self::dic()->ui()->mainTemplate()->addOnLoadCode($code);
	}


	public static function show() {
		self::initJS();
		$code = 'xoctWaiter.show();';
		self::dic()->ui()->mainTemplate()->addOnLoadCode($code);
	}
}

?>
