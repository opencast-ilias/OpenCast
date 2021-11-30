<?php

namespace srag\Plugins\Opencast\Util\Upload;

use ILIAS\Data\DataSize;
use ILIAS\Filesystem\DTO\Metadata as FileMetadata;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Filesystem;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use srag\Plugins\Opencast\Model\API\ACL\ACL;
use srag\Plugins\Opencast\UI\Input\Plupload;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use xoctUploadFile;

class UploadStorageService
{
    const TEMP_SUB_DIR = 'opencast';

    /**
     * @var Filesystem
     */
    private $fileSystem;
    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @param Filesystem $file_system
     */
    public function __construct(Filesystem $file_system, FileUpload $fileUpload)
    {
        $this->fileSystem = $file_system;
        $this->fileUpload = $fileUpload;
    }

    /**
     * @param UploadResult $uploadResult
     * @return string identifier
     */
    public function moveUploadToStorage(UploadResult $uploadResult) : string
    {
        $identifier = uniqid();
        $this->fileUpload->moveOneFileTo($uploadResult, $this->idToDirPath($identifier), Location::TEMPORARY);
        return $identifier;
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function delete(string $identifier) : void
    {
        $this->fileSystem->deleteDir($this->idToDirPath($identifier));
    }

    /**
     * @param string $identifier
     * @param int $fileSizeUnit
     * @return array{path: string, size: DataSize, name: string, mimeType: string}
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function getFileInfo(string $identifier, int $fileSizeUnit = DataSize::Byte) : array
    {
        $metadata = $this->idToFileMetadata($identifier);
        // TODO: check if a FileStream can be sent instead of hard-coding the ilias temp dir
        return [
            'path' => ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $metadata->getPath(),
            'size' => $this->fileSystem->getSize($metadata->getPath(), $fileSizeUnit),
            'name' => pathinfo($metadata->getPath(), PATHINFO_FILENAME),
            'mimeType' => $this->fileSystem->getMimeType($metadata->getPath())
        ];
    }

    public function buildACLUploadFile(ACL $acl): xoctUploadFile
    {
        $tmp_name = uniqid('tmp');
        $this->fileSystem->write($this->idToDirPath($tmp_name), (new ACLtoXML($acl))->getXML());
        $upload_file = new xoctUploadFile();
        $upload_file->setFileSize($this->fileSystem->getSize($this->idToDirPath($tmp_name), DataSize::Byte)
            ->getSize());
        $upload_file->setPostVar('attachment');
        $upload_file->setTitle('attachment');
        $upload_file->setPath(ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $this->idToDirPath($tmp_name));
        return $upload_file;
    }

    private function idToDirPath(string $identifier) : string
    {
        return self::TEMP_SUB_DIR . '/' . $identifier;
    }

    /**
     * @throws FileNotFoundException
     */
    private function idToFileMetadata(string $identifier) : FileMetadata
    {
        $dir = $this->idToDirPath($identifier);
        foreach ($this->fileSystem->finder()->in([$dir]) as $file) {
            return $file;
        }
        throw new FileNotFoundException();
    }
}