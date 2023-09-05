<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;

class MDFieldConfigEventAR extends MDFieldConfigAR
{
    public const TABLE_NAME = 'xoct_md_field_event';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }
}
