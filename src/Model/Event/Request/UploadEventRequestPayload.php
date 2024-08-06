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
    public function __construct(
        protected Metadata $metadata,
        protected ACL $acl,
        protected Processing $processing,
        protected \xoctUploadFile $presentation,
        /**
         * @var xoctUploadFile[]
         */
        protected array $subtitles = [],
        protected ?\xoctUploadFile $thumbnail = null
    ) {
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
        return $this->subtitles !== [];
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
