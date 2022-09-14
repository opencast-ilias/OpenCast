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
    )
    {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->scheduling = $scheduling;
        $this->processing = $processing;
    }


    public function jsonSerialize()
    {
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
            'acl' => json_encode($this->acl),
            'scheduling' => json_encode($this->scheduling->jsonSerialize()),
            'processing' => json_encode($this->processing)
        ];
    }
}
