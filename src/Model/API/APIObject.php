<?php

namespace srag\Plugins\Opencast\Model\API;

use DateTime;
use ilStr;
use ReflectionClass;
use ReflectionException;
use srag\Plugins\Opencast\Model\Cache\CacheFactory;
use stdClass;
use xoctException;
use xoctLog;

/**
 * Class Object
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
abstract class APIObject
{
    public const CACHE_TTL = 60 * 60 * 24;

    /**
     * @var array
     */
    protected static $cache = [];
    /**
     * @var bool
     */
    protected $loaded = false;


    /**
     * @param string $identifier
     *
     * @return APIObject
     */
    public static function find(string $identifier)
    {
        $class_name = get_called_class();
        $key = $class_name . '-' . $identifier;
        if (self::$cache[$key] instanceof $class_name) {
            return self::$cache[$key];
        }
        $existing = CacheFactory::getInstance()->get($key);

        if ($existing) {
            xoctLog::getInstance()->write('CACHE: used cached: ' . $key, xoctLog::DEBUG_LEVEL_2);

            return $existing;
        }
        xoctLog::getInstance()->write('CACHE: cached not used: ' . $key, xoctLog::DEBUG_LEVEL_2);
        $instance = new $class_name($identifier);
        self::cache($identifier, $instance);

        return $instance;
    }


    /**
     * @param          $identifier
     * @param stdClass $stdClass
     *
     * @return APIObject
     * @throws xoctException
     */
    public static function findOrLoadFromStdClass(string $identifier, stdClass $stdClass)
    {
        $class_name = get_called_class();
        $key = $class_name . '-' . $identifier;
        if (self::$cache[$key] instanceof $class_name) {
            return self::$cache[$key];
        }
        $existing = CacheFactory::getInstance()->get($key);

        if ($existing) {
            xoctLog::getInstance()->write('CACHE: used cached: ' . $key, xoctLog::DEBUG_LEVEL_2);

            return $existing;
        }

        xoctLog::getInstance()->write('CACHE: cached not used: ' . $key, xoctLog::DEBUG_LEVEL_2);
        /**
         * @var $instance APIObject
         */
        $instance = new $class_name();
        $instance->loadFromStdClass($stdClass);
        self::cache($identifier, $instance);

        return $instance;
    }


    /**
     * @param $identifier
     */
    public static function removeFromCache(string $identifier)
    {
        $class_name = get_called_class();
        $key = $class_name . '-' . $identifier;
        self::$cache[$key] = null;
        xoctLog::getInstance()->write('CACHE: removed from cache: ' . $key, xoctLog::DEBUG_LEVEL_1);
        CacheFactory::getInstance()->delete($key);
    }


    /**
     * @param            $identifier
     * @param APIObject  $object
     */
    public static function cache(string $identifier, APIObject $object)
    {
        $class_name = get_class($object);
        $key = $class_name . '-' . $identifier;
        self::$cache[$key] = $object;
        xoctLog::getInstance()->write('CACHE: added to cache: ' . $key, xoctLog::DEBUG_LEVEL_1);
        CacheFactory::getInstance()->set($key, $object, self::CACHE_TTL);
    }




    /**
     * @param $class
     *
     * @throws xoctException
     */
    public function loadFromStdClass(stdClass $class)
    {
        if (!$class instanceof stdClass) {
            throw new xoctException(xoctException::API_CALL_STATUS_500);
        }
        $array = (array) $class;
        $this->loadFromArray($array);
        $this->setLoaded(true);
    }


    /**
     * @param $array
     */
    public function loadFromArray(array $array)
    {
        foreach ($array as $k => $v) {
            $this->{$this->mapKey($k)} = $this->wakeup($k, $v);
        }
        $this->afterObjectLoad();
        $this->setLoaded(true);
    }


    /**
     * @param $key
     *
     * @return mixed
     */
    protected function mapKey($key)
    {
        return $key;
    }


    /**
     * @return boolean
     */
    public function isLoaded(): bool
    {
        return (bool) $this->loaded;
    }


    /**
     * @param boolean $loaded
     */
    public function setLoaded(bool $loaded)
    {
        $this->loaded = $loaded;
    }


    /**
     * @param $fieldname
     * @param $value
     *
     * @return mixed
     */
    protected function sleep($fieldname, $value)
    {
        return $value;
    }


    /**
     * @param $fieldname
     * @param $value
     *
     * @return mixed
     */
    protected function wakeup($fieldname, $value)
    {
        return $value;
    }


    protected function afterObjectLoad()
    {
    }
}
