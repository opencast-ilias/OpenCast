<?php

declare(strict_types=1);

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
     * @var xoctUploadFile
     */
    protected $presentation;
    /**
     * @var xoctUploadFile[]
     */
    protected $subtitles;
    /**
     * @var ?xoctUploadFile
     */
    protected $thumbnail = null;

    public function __construct(
        Metadata $metadata,
        ACL $acl,
        Processing $processing,
        xoctUploadFile $presentation,
        array $subtitles = [],
        ?xoctUploadFile $thumbnail = null
    ) {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->processing = $processing;
        $this->presentation = $presentation;
        $this->subtitles = $subtitles;
        $this->thumbnail = $thumbnail;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getAcl(): ?ACL
    {
        return $this->acl;
    }

    public function getProcessing(): ?Processing
    {
        return $this->processing;
    }

    public function getPresentation(): xoctUploadFile
    {
        return $this->presentation;
    }

    public function getSubtitles(): array
    {
        return $this->subtitles;
    }

    public function hasSubtitles(): bool
    {
        return count($this->subtitles) > 0;
    }

    public function getThumbnail(): xoctUploadFile
    {
        return $this->thumbnail;
    }

    public function hasThumbnail(): bool
    {
        return !empty($this->thumbnail);
    }

    /**
     * @return array{metadata: string, acl: string, presentation: mixed, processing: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'metadata' => json_encode([$this->metadata->withoutEmptyFields()->jsonSerialize()]),
            'acl' => json_encode($this->acl),
            'presentation' => $this->presentation->getCURLFile(),
            'processing' => json_encode($this->processing)
        ];
    }
}
