<?php

namespace srag\Plugins\Opencast\Model\Event\Request;

use CURLFile;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;
use xoctUploadFile;

class UploadEventRequestPayload
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
     * @var ?Processing
     */
    protected $processing;
    /**
     * @var CURLFile
     */
    protected $presentation;

    public function __construct(Metadata    $metadata,
                                ACL        $acl,
                                Processing $processing,
                                xoctUploadFile $presentation)
    {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->processing = $processing;
        $this->presentation = $presentation;
    }

    /**
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @return ACL|null
     */
    public function getAcl(): ?ACL
    {
        return $this->acl;
    }

    /**
     * @return Processing|null
     */
    public function getProcessing(): ?Processing
    {
        return $this->processing;
    }

    /**
     * @return xoctUploadFile
     */
    public function getPresentation(): xoctUploadFile
    {
        return $this->presentation;
    }


    public function jsonSerialize()
    {
        return [
            'metadata' => json_encode([$this->metadata->withoutEmptyFields()->jsonSerialize()]),
            'acl' => json_encode($this->acl),
            'presentation' => $this->presentation->getCURLFile(),
            'processing' => json_encode($this->processing)
        ];
    }
}