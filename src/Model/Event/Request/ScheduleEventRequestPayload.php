<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;

class ScheduleEventRequestPayload implements JsonSerializable
{
    public function __construct(protected Metadata $metadata, protected ?ACL $acl = null, protected ?Scheduling $scheduling = null, protected ?Processing $processing = null)
    {
    }

    /**
     * @return array{metadata: string, acl: string, scheduling: string, processing: string}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
            'acl' => json_encode($this->acl),
            'scheduling' => json_encode($this->scheduling->jsonSerialize()),
            'processing' => json_encode($this->processing)
        ];
    }
}
