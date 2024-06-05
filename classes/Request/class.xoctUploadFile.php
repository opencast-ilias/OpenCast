<?php

declare(strict_types=1);

use ILIAS\Data\DataSize;

/**
 * Class xoctUploadFile
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctUploadFile
{
    /**
     * @param $fileinfo array{path: string, size: DataSize, name: string, mimeType: string}
     *
     * @return xoctUploadFile
     */
    public static function getInstanceFromFileArray(array $fileinfo): self
    {
        $inst = new self();
        $inst->setTitle($fileinfo['name']);
        $inst->setPath($fileinfo['path']);
        $inst->setFileSize($fileinfo['size']->getSize());
        $inst->setMimeType($fileinfo['mimeType']);
        return $inst;
    }

    public function getCURLFile(): \CURLFile
    {
        // opencast doesn't like mimetype and name for some reason
        return new CURLFile($this->getPath());
    }

    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $title = '';
    /**
     * @var int
     */
    protected $file_size = 0;
    /**
     * @var string
     */
    protected $post_var = '';
    /**
     * @var string
     */
    protected $mime_type = '';

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->file_size;
    }

    /**
     * @param int $file_size
     */
    public function setFileSize($file_size): void
    {
        $this->file_size = $file_size;
    }

    /**
     * @return string
     */
    public function getPostVar()
    {
        return $this->post_var;
    }

    /**
     * @param string $post_var
     */
    public function setPostVar($post_var): void
    {
        $this->post_var = $post_var;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * @param string $mime_type
     */
    public function setMimeType($mime_type): void
    {
        $this->mime_type = $mime_type;
    }

    /**
     * @return resource filestream
     */
    public function getFileStream()
    {
        return fopen($this->getPath(), 'rb');
    }
}
