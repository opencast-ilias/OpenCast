<?php

namespace srag\Plugins\Opencast\Model\API\Metadata;

use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\MetadataDIC;
use xoctException;
use xoctRequest;

class MetadataRepository
{

    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var MDParser
     */
    private $md_parser;

    public function __construct(Cache $cache, MetadataDIC $metadataDIC)
    {
        $this->cache = $cache;
        $this->md_parser = $metadataDIC->metadataParser();
    }

    /**
     * @throws xoctException
     */
    public function find(string $identifier) : Metadata
    {
        return $this->cache->get('event-md-' . $identifier)
            ?? $this->fetch($identifier);
    }

    /**
     * @param string $identifier
     * @return Metadata
     * @throws xoctException
     */
    public function fetch(string $identifier) : Metadata
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->metadata()->get()) ?? [];
        $metadata = $this->md_parser->parseAPIResponseEvent($data);
        $this->cache->set('event-md-' . $identifier, $metadata);
        return $metadata;
    }

    private function formatMDValue($value, MDDataType $type)
    {
        switch ($type->getTitle()) {
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_LONG:
                return $value;
            case MDDataType::TYPE_TEXT_ARRAY:

        }
    }
}