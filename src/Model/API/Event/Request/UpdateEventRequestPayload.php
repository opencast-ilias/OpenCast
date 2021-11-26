<?php

namespace srag\Plugins\Opencast\Model\API\Event;

use JsonSerializable;
use srag\Plugins\Opencast\Model\API\ACL\ACL;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\API\Scheduling\Processing;
use srag\Plugins\Opencast\Model\API\Scheduling\Scheduling;

class UpdateEventRequestPayload implements JsonSerializable
{
    /**
     * @var Metadata
     */
    protected $metadata;
    /**
     * @var ?ACL
     */
    protected $acl;
    /**
     * @var ?Scheduling
     */
    protected $scheduling;
    /**
     * @var ?Processing
     */
    protected $processing;

    public function __construct(Metadata    $metadata,
                                ?ACL        $acl = null,
                                ?Scheduling $scheduling = null,
                                ?Processing $processing = null)
    {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->scheduling = $scheduling;
        $this->processing = $processing;
    }


    public function jsonSerialize()
    {
        $data = [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
        ];
        if (!is_null($this->acl)) {
            $data['acl'] = json_encode($this->acl);
        }
        if (!is_null($this->scheduling)) {
            $data['scheduling'] = json_encode($this->scheduling);
        }
        if (!is_null($this->processing)) {
            $data['processing'] = json_encode($this->processing);
        }
        return $data;
    }
}