<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoct.php');
/**
 * Class xoctCacheFactory
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctCacheFactory {
	private static $cache_instance = null;

	/**
	 * Generates an new instance of the live voting service.
	 *
	 * @return xoctCache
	 */
	public static function getInstance() {

		if(self::$cache_instance === null)
		{
			if (xoct::is52()) {
				require ('Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Cache/v52/class.xoctCache.php');
				self::$cache_instance = xoctCache::getInstance('');
				self::$cache_instance->init();
			} else {
				require ('Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Cache/v51/class.xoctCache.php');
				self::$cache_instance = xoctCache::getCacheInstance();
			}


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