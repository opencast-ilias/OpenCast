<?php

namespace srag\Plugins\Opencast\Model\API;

use DateTime;
use ilStr;
use ReflectionClass;
use ReflectionException;
use stdClass;
use xoctCacheFactory;
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

    /**
     * @var array
     */
    protected static $cache = array();
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
        $existing = xoctCacheFactory::getInstance()->get($key);

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
        $existing = xoctCacheFactory::getInstance()->get($key);

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
        xoctCacheFactory::getInstance()->delete($key);
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
        xoctCacheFactory::getInstance()->set($key, $object);
    }


    /**
     * @return array
     * @throws ReflectionException
     */
    public function __toArray()
    {
        $data = $this->__toStdClass();
        $array = (array) $data;

        return $array;
    }


    /**
     * @param string $separator
     * @param string $line_separator
     *
     * @return string
     * @throws ReflectionException
     */
    public function __toCsv(string $separator = ';', string $line_separator = "\n\r")
    {
        $csv = '';
        foreach ($this->__toArray() as $k => $v) {
            switch (true) {
                case $v instanceof DateTime:
                    $csv .= $k . $separator . date(DATE_ISO8601, $v->getTimestamp()) . $line_separator;
                    break;
                case $v instanceof stdClass:
                case is_array($v):
                case $v === null:
                case $v === false:
                case $v === '':
                    break;
                default:
                    $csv .= $k . $separator . $v . $line_separator;
                    break;
            }
        }

        return $csv;
    }


    /**
     * @return string
     * @throws ReflectionException
     */
    public function __toJSON() : string
    {
        return json_encode($this->__toStdClass());
    }


    /**
     * @return stdClass
     * @throws ReflectionException
     */
    public function __toStdClass() : stdClass
    {
        $r = new ReflectionClass($this);
        $stdClass = new stdClass();
        foreach ($r->getProperties() as $name) {
            $key = utf8_encode($name->getName());

            if ($key == 'cache') {
                continue;
            }

            $value = $this->sleep($key, $this->{$key});
            switch (true) {
                case ($value instanceof APIObject):
                    $stdClass->{$key} = $value->__toStdClass();
                    break;
                case (is_array($value)):
                    $a = array();
                    foreach ($value as $k => $v) {
                        if ($v instanceof APIObject) {
                            $a[$k] = $v->__toStdClass();
                        } elseif (is_array($v)) {
                            $a[$k] = (object) $v;
                        } else {
                            $a[$k] = self::convertToUtf8($v);
                        }
                    }
                    $stdClass->{$key} = $a;
                    break;
                case (is_bool($value)):
                    $stdClass->{$key} = $value;
                    break;
                case ($value instanceof DateTime):
                    $stdClass->{$key} = $value->getTimestamp();
                    break;
                case ($value instanceof stdClass):
                    $a = array();
                    $value = (array) $value;
                    foreach ($value as $k => $v) {
                        if ($v instanceof APIObject) {
                            $a[$k] = $v->__toStdClass();
                        } else {
                            $a[$k] = self::convertToUtf8($v);
                        }
                    }
                    $stdClass->{$key} = $a;
                    break;
                default:
                    $stdClass->{$key} = self::convertToUtf8($value);
                    break;
            }
        }

        return $stdClass;
    }


    /**
     * @param $string
     *
     * @return string
     */
    public static function convertToUtf8($string)
    {
        if (is_object($string) || ilStr::isUtf8($string)) {
            return $string;
        }

        return utf8_encode($string);
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
     * @param $json_string
     */
    public function loadFromJson(string $json_string)
    {
        $array = json_decode($json_string, true);
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
    public function isLoaded() : bool
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

