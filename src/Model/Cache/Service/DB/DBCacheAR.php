<?php

namespace srag\Plugins\Opencast\Model\Cache\Service\DB;

use ActiveRecord;

/**
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class DBCacheAR extends ActiveRecord
{
    const TABLE_NAME = 'xoct_cache';

    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     128
     * @con_is_notnull true
     * @con_is_primary true
     * @con_is_unique  true
     */
    protected $identifier;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  clob
     * @con_is_notnull true
     */
    protected $value;
    /**
     * @var int timestamp
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     * @con_is_notnull false
     */
    protected $expires;

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)/* : void*/
    {
        $this->identifier = $identifier;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)/* : void*/
    {
        $this->value = $value;
    }

    /**
     * @param int|null $expires
     */
    public function setExpires(/*?int*/ $expires)/* : void*/
    {
        $this->expires = $expires;
    }
    

    /**
     * @return string
     */
    public function getIdentifier() : string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * @return int|null
     */
    public function getExpires()/* : ?int*/
    {
        return $this->expires;
    }


    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }

}
