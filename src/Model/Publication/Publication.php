<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\Model\API\APIObject;

/**
 * Class publication
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class Publication extends APIObject
{
    /**
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->setId($id);
        if ($id === '') {
            return;
        }
        if ($id === '0') {
            return;
        }
        $this->read();
    }

    public function read(): void
    {
    }

    /**
     * @param $array
     * @throws \xoctException
     */
    public function loadFromArray(array $array): void
    {
        parent::loadFromArray($array);
        $attachments = [];
        foreach ($this->getAttachments() as $attachment) {
            $xoctAttachment = new Attachment();
            $xoctAttachment->loadFromStdClass($attachment);
            $attachments[] = $xoctAttachment;
        }
        $this->setAttachments($attachments);

        $medias = [];
        foreach ($this->getMedia() as $media) {
            $xoctMedia = new Media();
            $xoctMedia->loadFromStdClass($media);
            $medias[] = $xoctMedia;
        }
        $this->setMedia($medias);

        $metadata = [];
        foreach ($this->getMetadata() as $mtd) {
            $xoctMetadata = new Metadata();
            $xoctMetadata->loadFromStdClass($mtd);
            $metadata[] = $xoctMetadata;
        }
        $this->setMetadata($metadata);
    }

    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $channel;
    /**
     * @var string
     */
    protected $mediatype;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var Media[]
     */
    protected $media;
    /**
     * @var Attachment[]
     */
    protected $attachments;
    /**
     * @var Metadata[]
     */
    protected $metadata;
    /**
     * @var string
     */
    public $usage_type;
    /**
     * @var string
     */
    public $usage_id;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }

    /**
     * @return string
     */
    public function getMediatype()
    {
        return $this->mediatype;
    }

    /**
     * @param string $mediatype
     */
    public function setMediatype($mediatype): void
    {
        $this->mediatype = $mediatype;
    }

    /**
     * @return Media[]
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param Media[] $media
     */
    public function setMedia($media): void
    {
        $this->media = $media;
    }

    /**
     * @return Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param Attachment[] $attachments
     */
    public function setAttachments($attachments): void
    {
        $this->attachments = $attachments;
    }

    /**
     * @param Metadata[] $metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
    * @param Metadata[] $Metadata
    */
    public function setMetadata($metadata): void
    {
        $this->metadata = $metadata;
    }
}
