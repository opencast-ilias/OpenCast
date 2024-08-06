<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\Model\API\APIObject;

/**
 * Class PublicationMetadata
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
#[\AllowDynamicProperties]
class PublicationMetadata extends APIObject
{
    public const MEDIA_TYPE_VIDEO = "video";
    public const ROLE_PRESENTATION = "presentation";
    public const ROLE_PRESENTER = "presenter";

    /**
     * @param string $id
     */
    public function __construct()
    {
    }

    /**
     * @return string {"presentation"|"presenter"}
     */
    public function getRole(): string
    {
        return str_contains(
            $this->getFlavor(),
            self::ROLE_PRESENTATION
        ) ? self::ROLE_PRESENTATION : self::ROLE_PRESENTER;
    }

    /**
     * @var string
     */
    public $id = '';
    /**
     * @var string
     */
    public $mediatype;
    /**
     * @var string
     */
    public $url;
    /**
     * @var string
     */
    public $flavor;
    /**
     * @var int
     */
    public $size;
    /**
     * @var int
     */
    public $checksum;
    /**
     * @var array
     */
    public $tags;
    /**
     * @var string
     */
    public $usage_type;
    /**
     * @var string
     */
    public $usage_id;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
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
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * @param string $flavor
     */
    public function setFlavor($flavor): void
    {
        $this->flavor = $flavor;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size): void
    {
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * @param int $checksum
     */
    public function setChecksum($checksum): void
    {
        $this->checksum = $checksum;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags): void
    {
        $this->tags = $tags;
    }
}
