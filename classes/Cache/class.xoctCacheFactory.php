<?php
/**
 * Class xoctCacheFactory
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctCacheFactory {
	private static $cache_instance = null;

	/**
	 * This used to distinguish between the ILIAS 5.1 and 5.2 cache. Since 5.1 is no longer supported, this function
     * just returns a cache instance.
	 *
	 * @return xoctCache
	 */
	public static function getInstance() {

		if(self::$cache_instance === null)
		{

            self::$cache_instance = xoctCache::getInstance('');
            self::$cache_instance->init();

			/*
			 * caching adapter of the xlvoConf will call getInstance again,
			 * due to that we need to call the init logic after we created the
			 * cache in an deactivated state.
			 *
			 * The xlvoConf call gets the deactivated cache and query the value
			 * out of the database. afterwards the cache is turned on with this init() call.
			 *
			 * This must be considered as workaround and should be probably fixed in the next major release.
			 */
		}

		return self::$cache_instance;

	}
}