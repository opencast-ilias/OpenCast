<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

use srag\Plugins\Opencast\UI\EventFormBuilder;
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
        protected \xoctUploadFile $uploading_file,
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
        return $this->uploading_file;
    }

    public function getPresenter(): xoctUploadFile
    {
        return $this->uploading_file;
    }

    public function getUploadingFile(): xoctUploadFile
    {
        return $this->uploading_file;
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

    public function hasVideoFile(): bool
    {
        $file_mimetype = $this->uploading_file->getMimeType();
        if (in_array($file_mimetype, EventFormBuilder::$accepted_video_mimetypes)) {
            return true;
        }
        return false;
    }

    public function hasAudioFile(): bool
    {
        $file_mimetype = $this->uploading_file->getMimeType();
        if (in_array($file_mimetype, EventFormBuilder::$accepted_audio_mimetypes)) {
            return true;
        }
        return false;
    }

    /**
     * @return array{metadata: string, acl: string, presentation: mixed, processing: string}
     */
    public function jsonSerialize(): array
    {
        $serialized_array = [
            'metadata' => json_encode([$this->metadata->withoutEmptyFields()->jsonSerialize()]),
            'acl' => json_encode($this->acl),
            'processing' => json_encode($this->processing)
        ];

        if ($this->hasAudioFile()) {
            $serialized_array['presenter'] = $this->getPresenter()->getCURLFile();
        } else {
            $serialized_array['presentation'] = $this->getPresentation()->getCURLFile();
        }

        return $serialized_array;
    }
}
