<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

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


}