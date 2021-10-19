<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Series;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;

class MDFieldConfigSeriesRepository implements MDFieldConfigRepository
{
    /**
     * @return MDFieldConfigSeriesAR[]
     */
    public function getAll() : array
    {
        return MDFieldConfigSeriesAR::get();
    }

    public function getArray() : array
    {
        return MDFieldConfigSeriesAR::getArray();
    }
}