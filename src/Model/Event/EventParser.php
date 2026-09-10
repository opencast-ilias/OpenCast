<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event;

use srag\Plugins\Opencast\Model\ACL\ACLParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Scheduling\SchedulingParser;
use stdClass;

class EventParser
{
    public function __construct(
        private readonly MDParser $MDParser,
        private readonly ACLParser $ACLParser,
        private readonly SchedulingParser $schedulingParser,
        private readonly EventAdditionsRepository $additionsRepository,
        /**
         * ILIAS object the events are being parsed for. The online/offline state is stored per object,
         * so the same series linked in several objects can have different states. 0 when there is no
         * object context (e.g. cron, external API) - then the shared (obj_id 0) bucket is used.
         */
        private readonly int $obj_id
    ) {
    }

    public function parseAPIResponse(stdClass $data, string $identifier): Event
    {
        $event = new Event();
        $event->setPublicationStatus($data->publication_status);
        $event->setProcessingState($data->processing_state);
        $event->setStatus($data->status);
        $event->setHasPreviews($data->has_previews);
        $event->setXoctEventAdditions($this->additionsRepository->find($identifier, $this->obj_id));

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
