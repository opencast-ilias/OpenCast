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
    const TABLE_NAME = 'sr_chat_config';

    const C_IP = 'ip';
    const C_PORT = 'port';
    const C_PROTOCOL = 'protocol';
    const C_HOST = 'host';


    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @var array
     */
    protected static $cache = array();
    /**
     * @var array
     */
    protected static $cache_loaded = array();
    /**
     * @var bool
     */
    protected $ar_safe_read = false;

    /**
     * @param $name
     *
     * @return mixed
     */
    public static function getConfig($name) {
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
    public static function set($name, $value) {
        $obj = new self($name);
        $obj->setValue(json_encode($value));

        if (self::where(array( 'name' => $name ))->hasSets()) {
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
    public function setName($name) {
        $this->name = $name;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * @param string $value
     */
    public function setValue($value) {
        $this->value = $value;
    }


    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }
}