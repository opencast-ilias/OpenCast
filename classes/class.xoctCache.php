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
	}
}

?>
