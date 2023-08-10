<?php

namespace srag\Plugins\Opencast\Model\Event\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;

class ScheduleEventRequestPayload implements JsonSerializable
{
    /**
     * @var Metadata
     */
    protected $metadata;
    /**
     * @var ACL
     */
    protected $acl;
    /**
     * @var Scheduling
     */
    protected $scheduling;
    /**
     * @var Processing
     */
    protected $processing;

    public function __construct(
        Metadata $metadata,
        ACL $acl = null,
        Scheduling $scheduling = null,
        Processing $processing = null
    ) {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->scheduling = $scheduling;
        $this->processing = $processing;
    }

    /**
     * @return array{metadata: string, acl: string, scheduling: string, processing: string}
     */
    public function jsonSerialize()
    {
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()], JSON_THROW_ON_ERROR),
            'acl' => json_encode($this->acl, JSON_THROW_ON_ERROR),
            'scheduling' => json_encode($this->scheduling->jsonSerialize(), JSON_THROW_ON_ERROR),
            'processing' => json_encode($this->processing, JSON_THROW_ON_ERROR)
        ];
    }
}
