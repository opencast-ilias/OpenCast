<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;

class MDFieldConfigEventAR extends MDFieldConfigAR
{
    const TABLE_NAME = 'md_field_event';

    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }
}