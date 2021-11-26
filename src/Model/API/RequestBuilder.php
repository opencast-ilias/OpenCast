<?php

namespace srag\Plugins\Opencast\Model\API;

use srag\Plugins\Opencast\Model\API\Event\UpdateEventRequest;
use srag\Plugins\Opencast\Model\API\Event\UpdateEventRequestPayload;
use xoctEvent;

class RequestBuilder
{

    public function updateEvent(xoctEvent $event) : UpdateEventRequest
    {
        return new UpdateEventRequest($event->getIdentifier(), new UpdateEventRequestPayload(
            $event->getMetadata(),
            $event->getAcl(),
            $event->isScheduled() ? $event->getScheduling() : null,
            $event->isScheduled() ? $event->getProcessing() : null
        ));
    }
}