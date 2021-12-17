<?php

namespace srag\Plugins\Opencast\Model\Series;

use srag\Plugins\Opencast\Model\ACL\ACLParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use stdClass;
use xoctSeries;

class SeriesParser
{
    /**
     * @var ACLParser
     */
    private $ACLParser;
    /**
     * @var MDParser
     */
    private $MDParser;

    /**
     * @param ACLParser $ACLParser
     * @param MDParser $MDParser
     */
    public function __construct(ACLParser $ACLParser, MDParser $MDParser)
    {
        $this->ACLParser = $ACLParser;
        $this->MDParser = $MDParser;
    }


    public function parseAPIResponse(stdClass $data, string $identifier) : xoctSeries
    {
        $series = new xoctSeries();
        $series->setIdentifier($identifier);
        $series->setTitle($data->title);
        $series->setTheme($data->theme);
        $series->setAccessPolicies($this->ACLParser->parseAPIResponse($data->acl));
        $series->setMetadata($this->MDParser->parseAPIResponseSeries($data->metadata));
        return $series;
    }
}