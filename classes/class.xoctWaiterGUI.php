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


	public static function init() {
		global $tpl;
		if (! self::$init) {
			$tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.js');
			$tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.css');
			self::$init = true;
		}
	}
}

?>
