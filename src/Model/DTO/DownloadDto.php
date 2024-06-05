<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\DTO;

/**
 * Class DownloadDto
 * @package srag\Plugins\Opencast\Model\DTO
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class DownloadDto
{
    private string $publication_id;

    private string $resolution;
    private string $url;

    /**
     * DownloadDto constructor.
     */
    public function __construct(string $publication_id, string $resolution, string $url = '')
    {
        $this->publication_id = $publication_id;
        $this->resolution = $resolution;
        $this->url = $url;
    }

    public function getPublicationId(): string
    {
        return $this->publication_id;
    }

    public function getResolution(): string
    {
        return $this->resolution;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
