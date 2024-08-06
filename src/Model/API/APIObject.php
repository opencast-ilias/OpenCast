<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\API;

use stdClass;
use xoctException;
use xoctLog;
use srag\Plugins\Opencast\Model\Cache\Container\Request;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class Object
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
abstract class APIObject implements Request
{
    /**
     * @var bool
     */
    protected $loaded = false;

    public function getContainerKey(): string
    {
        return 'api_object';
    }

    /**
     * @return APIObject
     */
    public static function find(string $identifier)
    {
        $opencastContainer = Init::init();
        /** @var Services $cache_services */
        $cache_services = $opencastContainer[Services::class];
        $container = $cache_services->get(new static());

        $class_name = static::class;
        $key = $class_name . '-' . $identifier;
        if ($container->has($key)) {
            $data = $container->get($key);
        }


        xoctLog::getInstance()->write('CACHE: cached not used: ' . $key, xoctLog::DEBUG_LEVEL_2);
        $instance = new $class_name($identifier);
        self::cache($identifier, $instance);

        return $instance;
    }

    /**
     * @param            $identifier
     */
    public static function cache(string $identifier, APIObject $object): void
    {
        $opencastContainer = Init::init();
        /** @var Services $cache_services */
        $cache_services = $opencastContainer[Services::class];
        $container = $cache_services->get($object);

        $class_name = $object::class;
        $key = $class_name . '-' . $identifier;
        xoctLog::getInstance()->write('CACHE: added to cache: ' . $key, xoctLog::DEBUG_LEVEL_1);

        $container->set($key, $object->getAsArray());
    }

    /**
     * @param $class
     *
     * @throws xoctException
     */
    public function loadFromStdClass(stdClass $class): void
    {
        $array = (array) $class;
        $this->loadFromArray($array);
        $this->setLoaded(true);
    }

    /**
     * @param $array
     */
    public function loadFromArray(array $array): void
    {
        foreach ($array as $k => $v) {
            $this->{$this->mapKey($k)} = $this->wakeup($k, $v);
        }
        $this->afterObjectLoad();
        $this->setLoaded(true);
    }

    protected function getAsArray(): array
    {
        $r = new \ReflectionClass($this);
        $array = [];
        foreach ($r->getProperties() as $p) {
            $p->setAccessible(true);
            $value = $p->getValue($this);
            if (is_object($value)) {
                continue;
            }
            $array[$p->getName()] = $this->sleep($p->getName(), $value);
        }
        return $array;
    }

    protected function getAsStdClass(): stdClass
    {
        return (object) $this->getAsArray();
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

    public function isLoaded(): bool
    {
        return (bool) $this->loaded;
    }

    public function setLoaded(bool $loaded): void
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
