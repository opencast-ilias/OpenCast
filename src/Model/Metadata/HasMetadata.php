<?php

namespace srag\Plugins\Opencast\Model\Metadata;

interface HasMetadata
{
    public function getMetadataValue(string $metadata_field);
}