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
    /**
     * DownloadDto constructor.
     */
    public function __construct(private readonly string $publication_id, private readonly string $resolution, private readonly string $url = '')
    {
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
