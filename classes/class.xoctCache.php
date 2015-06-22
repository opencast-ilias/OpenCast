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
		return new self(self::TYPE_APC, self::COMP_OPENCAST);
	}


	/**
	 * @return bool
	 */
	public function isActive() {
		return self::isOverrideActive();
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


	/**
	 * @param      $key
	 * @param      $value
	 * @param null $ttl
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl = NULL) {
		if (! $this->isActive()) {
			return false;
		}
		$this->global_cache->setValid($key);

		return $this->global_cache->set($key, $this->global_cache->serialize($value), $ttl);
	}


	public function get($key) {
		if (! $this->isActive()) {
			return false;
		}
		$unserialized_return = $this->global_cache->unserialize($this->global_cache->get($key));
		if ($unserialized_return) {

			if ($this->global_cache->isValid($key)) {

				return $unserialized_return;
			} else {
				//				var_dump($key); // FSX
			}
		}

		return NULL;
	}
}

?>
