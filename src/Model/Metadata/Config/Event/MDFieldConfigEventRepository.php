<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;

class MDFieldConfigEventRepository implements MDFieldConfigRepository
{
    /**
     * @return MDFieldConfigEventAR[]
     */
    public function getAll() : array
    {
        return MDFieldConfigEventAR::get();
    }

    public function getArray() : array
    {
        return MDFieldConfigEventAR::getArray();
    }
}