<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

use xoctException;

interface MDFieldConfigRepository
{

    /**
     * @return MDFieldConfigAR[]
     */
    public function getAll() : array;

    /**
     * @return array
     */
    public function getArray() : array;

    /**
     * @throws xoctException
     */
    public function findByFieldId($field_id) : MDFieldConfigAR;

    public function createFromArray($data) : MDFieldConfigAR;


}