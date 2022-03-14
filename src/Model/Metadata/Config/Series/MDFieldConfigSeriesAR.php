<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Series;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;

class MDFieldConfigSeriesAR extends MDFieldConfigAR
{
    const TABLE_NAME = 'xoct_md_field_series';

    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }
}