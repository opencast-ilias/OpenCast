<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use srag\Plugins\Opencast\Model\API\Metadata\Metadata;

interface HasMetadata
{
    public function getMetadata(string $metadata_field) : Metadata;
}