<?php

namespace srag\Plugins\Opencast\Model\DTO;

/**
 * Class DownloadDto
 * @package srag\Plugins\Opencast\Model\DTO
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class DownloadDto
{
    /**
     * @var string
     */
    private $publication_id;

    /**
     * @var string
     */
    private $resolution;
    /**
     * @var string
     */
    private $url;

    /**
     * DownloadDto constructor.
     * @param string $publication_id
     * @param string $resolution
     * @param string $url
     */
    public function __construct(string $publication_id, string $resolution, string $url)
    {
        $this->publication_id = $publication_id;
        $this->resolution = $resolution;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getPublicationId() : string
    {
        return $this->publication_id;
    }

    /**
     * @return string
     */
    public function getResolution() : string
    {
        return $this->resolution;
    }

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

}
