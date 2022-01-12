<?php

namespace srag\Plugins\Opencast\Model\Series;

use srag\Plugins\Opencast\Model\ACL\ACLParser;
use stdClass;

class SeriesParser
{
    /**
     * @var ACLParser
     */
    private $ACLParser;

    public function __construct(ACLParser $ACLParser)
    {
        $this->ACLParser = $ACLParser;
    }


    public function parseAPIResponse(stdClass $data) : Series
    {
        $series = new Series();
        $series->setIdentifier($data->identifier);
        $series->setAccessPolicies($this->ACLParser->parseAPIResponse($data->acl));
        $series->setMetadata($data->metadata);
        if (is_int($data->theme)) {
            $series->setTheme($data->theme);
        }
        return $series;
    }
}