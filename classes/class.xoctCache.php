<?php
require_once('./Services/GlobalCache/classes/class.ilGlobalCache.php');

/**
 * Class xoctCache
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctCache extends ilGlobalCache {

	const COMP_OPENCAST = 'xoct';
	/**
	 * @var bool
	 */
	protected static $override_active = false;
	/**
	 * @var array
	 */
	protected static $active_components = array(
		self::COMP_OPENCAST,
	);


	/**
	 * @return ilGlobalCache
	 */
	public static function getInstance() {
		$component = self::COMP_OPENCAST;
		if (! isset(self::$instances[$component])) {
			$service_type = self::getComponentType($component);
			$service_type = self::TYPE_APC;
			$ilGlobalCache = new self($service_type, $component);

			self::$instances[$component] = $ilGlobalCache;
		}

		return self::$instances[$component];
	}


	/**
	 * @return bool
	 */
	public function isActive() {
		return true;
//		return self::isOverrideActive();
	}


	/**
	 * @return boolean
	 */
	public static function isOverrideActive() {
		return self::$override_active;
	}


	/**
	 * @param boolean $override_active
	 */
	public static function setOverrideActive($override_active) {
		self::$override_active = $override_active;
	}
}

?>
