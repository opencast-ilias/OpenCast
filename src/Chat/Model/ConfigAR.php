<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\Model;

use ActiveRecord;

/**
 * Class ChatConfig
 *
 * @package srag\Plugins\Opencast\Chat\Model
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
#[\AllowDynamicProperties]
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
     * @return mixed
     */
    public static function getConfig(string $name)
    {
        if (!isset(self::$cache_loaded[$name])) {
            $obj = new self($name);
            self::$cache[$name] = $obj->getValue();
            self::$cache_loaded[$name] = true;
        }

        return self::$cache[$name];
    }

    public static function set(string $name, mixed $value): void
    {
        $obj = new self($name);
        $obj->setValue(json_encode($value));

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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
