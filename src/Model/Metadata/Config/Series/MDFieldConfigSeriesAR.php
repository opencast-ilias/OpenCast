<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata\Config\Series;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;

class MDFieldConfigSeriesAR extends MDFieldConfigAR
{
    public const TABLE_NAME = 'xoct_md_field_series';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }
}
