<?php

namespace srag\Plugins\Opencast\Chat\Model;

use ActiveRecord;

/**
 * Class ChatConfig
 *
 * @package srag\Plugins\Opencast\Chat\Model
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ConfigAR extends ActiveRecord
{
    public const TABLE_NAME = 'sr_chat_config';

    public const C_IP = 'ip';
    public const C_PORT = 'port';
    public const C_PROTOCOL = 'protocol';
    public const C_HOST = 'host';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var array
     */
    protected static $cache = [];
    /**
     * @var array
     */
    protected static $cache_loaded = [];
    /**
     * @var bool
     */
    protected $ar_safe_read = false;

    /**
     * @param $name
     *
     * @return mixed
     */
    public static function getConfig($name)
    {
        if (!self::$cache_loaded[$name]) {
            $obj = new self($name);
            self::$cache[$name] = $obj->getValue();
            self::$cache_loaded[$name] = true;
        }

        return self::$cache[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public static function set($name, $value): void
    {
        $obj = new self($name);
        $obj->setValue(json_encode($value, JSON_THROW_ON_ERROR));

        if (self::where(['name' => $name])->hasSets()) {
            $obj->update();
        } else {
            $obj->create();
        }
    }

    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected $name;
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           4000
     */
    protected $value;

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
