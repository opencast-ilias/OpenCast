<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event;

use srag\Plugins\Opencast\Model\ACL\ACLParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Scheduling\SchedulingParser;
use stdClass;

class EventParser
{
    public function __construct(private readonly MDParser $MDParser, private readonly ACLParser $ACLParser, private readonly SchedulingParser $schedulingParser)
    {
    }

    public function parseAPIResponse(stdClass $data, string $identifier): Event
    {
        $event = new Event();
        $event->setPublicationStatus($data->publication_status);
        $event->setProcessingState($data->processing_state);
        $event->setStatus($data->status);
        $event->setHasPreviews($data->has_previews);
        $event->setXoctEventAdditions(EventAdditionsAR::findOrGetInstance($identifier));

        if (isset($data->metadata)) {
            $event->setMetadata($this->MDParser->getMetadataFromResponse($data->metadata));
        } else {
            $event->setMetadata($this->MDParser->getMetadataFromData($data));
        }

        if (isset($data->acl)) {
            $event->setAcl($this->ACLParser->parseAPIResponse($data->acl));
        }

        if (isset($data->publications)) {
            // todo: publications should have a parser as well
            $event->publications()->loadFromArray($data->publications);
        }

        if ($event->isScheduled() && isset($data->scheduling)) {
            $event->setScheduling($this->schedulingParser->parseApiResponse($data->scheduling));
        }
        return $event;
    }
}
