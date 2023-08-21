<?php

namespace srag\Plugins\Opencast\Model\Cache;

use Exception;
use ilGlobalCache;
use ilGlobalCacheService;
use ilOpenCastPlugin;
use RuntimeException;
use srag\Plugins\Opencast\Model\Cache\Service\DB\DBCacheService;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoctLog;

/**
 * @author  Theodor Truffer <theo@fluxlabs.ch>
 * @version 1.0.0
 */
class Cache extends ilGlobalCache
{
    public const COMP_PREFIX = ilOpenCastPlugin::PLUGIN_ID;
    /**
     * @var bool
     */
    protected static $override_active = false;
    /**
     * @var array
     */
    protected static $active_components = [
        self::COMP_PREFIX,
    ];

    /**
     * @return Cache
     */
    public static function getInstance($component)
    {
        $service_type = self::getSettings()->getService();
        $cache = new self($service_type);

        $cache->setActive(false);
        self::setOverrideActive(false);

        return $cache;
    }


    //	/**
    //	 * @param null $component
    //	 *
    //	 * @return ilGlobalCache|void
    //	 * @throws ilException
    //	 */
    //	public static function getInstance($component) {
    //		throw new ilException('xoctCache::getInstance() should not be called. Please call xoctCache::getCacheInstance() instead.');
    //	}

    public function init(): void
    {
        $this->initCachingService();
        $this->setActive(true);
        self::setOverrideActive(true);
    }

    protected function initCachingService(): void
    {
        /**
         * @var $ilGlobalCacheService ilGlobalCacheService
         */
        if ($this->getComponent() === '' || $this->getComponent() === '0') {
            $this->setComponent(ilOpenCastPlugin::PLUGIN_NAME);
        }

        switch ($this->getCacheType()) {
            case PluginConfig::CACHE_STANDARD:
                $serviceName = self::lookupServiceClassName($this->getServiceType());
                $ilGlobalCacheService = new $serviceName(self::$unique_service_id, $this->getComponent());
                $ilGlobalCacheService->setServiceType($this->getServiceType());
                break;
            case PluginConfig::CACHE_DATABASE:
                $ilGlobalCacheService = new DBCacheService(self::$unique_service_id, $this->getComponent());
                $ilGlobalCacheService->setServiceType(DBCacheService::TYPE_DB);
                break;
            case PluginConfig::CACHE_DISABLED:
            default:
                $serviceName = self::lookupServiceClassName(self::TYPE_STATIC);
                $ilGlobalCacheService = new $serviceName(self::$unique_service_id, $this->getComponent());
                $ilGlobalCacheService->setServiceType(self::TYPE_STATIC);
                break;
        }

        $this->global_cache = $ilGlobalCacheService;
        $this->setActive(in_array($this->getComponent(), self::getActiveComponents()));
    }

    /**
     * Checks if live voting is able to use the global cache.
     *
     * @return int
     */
    private function getCacheType()
    {
        try {
            return (int) PluginConfig::getConfig(PluginConfig::F_ACTIVATE_CACHE);
        } catch (Exception $exception) { //catch exception while dbupdate is running. (xoctConf is not ready at that time).
            return PluginConfig::CACHE_DISABLED;
        }
    }

    /**
     * @param $service_type
     *
     * @return string
     */
    public static function lookupServiceClassName($service_type)
    {
        switch ($service_type) {
            case self::TYPE_APC:
                return 'ilApc';
            case self::TYPE_MEMCACHED:
                return 'ilMemcache';
            case self::TYPE_XCACHE:
                return 'ilXcache';
            case DBCacheService::TYPE_DB:
                return 'DBCacheService';
            case self::TYPE_STATIC:
            default:
                return 'ilStaticCache';
        }
    }

    /**
     * @return array
     */
    public static function getActiveComponents()
    {
        return self::$active_components;
    }

    /**
     * @param bool $complete
     *
     * @return bool
     * @throws RuntimeException
     */
    public function flush($complete = false)
    {
        if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
            return false;
        }

        return parent::flush(true);
    }

    /**
     * @param $key
     *
     * @return bool
     * @throws RuntimeException
     */
    public function delete($key)
    {
        if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
            return false;
        }

        xoctLog::getInstance()->write('CACHE: removed from cache: ' . $key, xoctLog::DEBUG_LEVEL_1);
        return parent::delete($key);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return self::isOverrideActive();
    }

    /**
     * @return boolean
     */
    public static function isOverrideActive()
    {
        return self::$override_active;
    }

    /**
     * @param boolean $override_active
     */
    public static function setOverrideActive($override_active): void
    {
        self::$override_active = $override_active;
    }

    /**
     * @param      $key
     * @param      $value
     * @param null $ttl
     *
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        //		$ttl = $ttl ? $ttl : 480;
        if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
            return false;
        }

        $return = $this->global_cache->set($key, $this->global_cache->serialize($value), $ttl);
        xoctLog::getInstance()->write('CACHE: added to cache: ' . $key, xoctLog::DEBUG_LEVEL_1);

        return $return;
    }

    /**
     * @param $key
     *
     * @return bool|mixed|null
     */
    public function get($key)
    {
        if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
            return false;
        }
        $unserialized_return = $this->global_cache->unserialize($this->global_cache->get($key));

        if ($unserialized_return) {
            xoctLog::getInstance()->write('CACHE: used cached: ' . $key, xoctLog::DEBUG_LEVEL_2);
            return $unserialized_return;
        }

        xoctLog::getInstance()->write('CACHE: cache not used: ' . $key, xoctLog::DEBUG_LEVEL_2);
        return null;
    }
}
