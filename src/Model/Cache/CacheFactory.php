<?php

namespace srag\Plugins\Opencast\Model\Cache;

/**
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class CacheFactory
{
    private static $cache_instance;

    /**
     * This used to distinguish between the ILIAS 5.1 and 5.2 cache. Since 5.1 is no longer supported, this function
     * just returns a cache instance.
     */
    public static function getInstance(): Cache
    {
        if (self::$cache_instance === null) {
            self::$cache_instance = Cache::getInstance('');
            self::$cache_instance->init();
        }

        return self::$cache_instance;
    }
}
