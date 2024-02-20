<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\FileTransfer;

use ILIAS\Data\DataSize;
use ILIAS\Filesystem\DTO\Metadata as FileMetadata;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Filesystem;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use xoctUploadFile;
use ILIAS\Filesystem\DTO\Metadata;

class UploadStorageService
{
    public const TEMP_SUB_DIR = 'opencast';

    /**
     * @var Filesystem
     */
    protected $fileSystem;
    /**
     * @var FileUpload
     */
    protected $fileUpload;

    public function __construct(Filesystem $file_system, FileUpload $fileUpload)
    {
        $this->fileSystem = $file_system;
        $this->fileUpload = $fileUpload;
    }

    /**
     * @return string identifier
     */
    public function moveUploadToStorage(UploadResult $uploadResult): string
    {
        $identifier = uniqid('', false);
        $this->fileUpload->moveOneFileTo($uploadResult, $this->idToDirPath($identifier), Location::TEMPORARY);
        return $identifier;
    }

    public function appendChunkToStorage(UploadResult $uploadResult, string $chunk_id): string
    {
        $path = $this->idToDirPath($chunk_id) . '/' . $uploadResult->getName();

        if ($this->fileSystem->has($path)) {
            $stream = fopen($this->fileSystem->readStream($path)->getMetadata()['uri'], 'a');
            fwrite($stream, file_get_contents($uploadResult->getPath()));
        } else {
            $this->fileSystem->write($path, file_get_contents($uploadResult->getPath()));
        }

        return $chunk_id;
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function delete(string $identifier): void
    {
        if (strlen($identifier) == 0) {
            return;
        }
        $dir = $this->idToDirPath($identifier);
        if ($this->fileSystem->hasDir($dir)) {
            $this->deleteDirRecursive($dir);
        }
    }

    private function deleteDirRecursive(string $dir): void
    {
        // the folders are sorted based on their path length to ensure that nested folders are deleted first
        // thereby preventing any issues due to deletion attempts on no longer existing folders.
        $folders = $this->fileSystem->finder()->in([$dir]);
        $folders = $folders->directories();
        $folders = $folders->sort(function (
            Metadata $a,
            Metadata $b
        ): int {
            return strlen($a->getPath()) - strlen($b->getPath());
        });
        $folders = $folders->reverseSorting();
        $folders = $folders->getIterator();
        $folders->rewind();
        while ($folders->valid()) {
            try {
                $folder_match = $folders->current();
                $path = $folder_match->getPath();
                if ($folder_match->isDir()) {
                    $this->fileSystem->deleteDir($path);
                }
                $folders->next();
            } catch (\Throwable $t) {
                $folders->next();
            }
        }
        try {
            $this->fileSystem->deleteDir($dir);
        } catch (\Throwable $t) {
        }
    }

    /**
     * @return array{path: string, size: DataSize, name: string, mimeType: string}
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function getFileInfo(string $identifier, int $fileSizeUnit = DataSize::Byte): array
    {
        $metadata = $this->idToFileMetadata($identifier);
        /** TODO: path is hard coded here because it's required to send the file via curlFile and I didn't find a way to get the path dynamically from the file service */
        return [
            'path' => ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $metadata->getPath(),
            'size' => $this->fileSystem->getSize($metadata->getPath(), $fileSizeUnit),
            'name' => pathinfo($metadata->getPath(), PATHINFO_FILENAME),
            'mimeType' => $this->fileSystem->getMimeType($metadata->getPath()),
            'id' => $identifier
        ];
    }

    public function buildACLUploadFile(ACL $acl): xoctUploadFile
    {
        $tmp_name = uniqid('tmp', false);
        $this->fileSystem->write($this->idToDirPath($tmp_name), (new ACLtoXML($acl))->getXML());
        $upload_file = new xoctUploadFile();
        $upload_file->setFileSize(
            $this->fileSystem->getSize($this->idToDirPath($tmp_name), DataSize::Byte)
                             ->getSize()
        );
        $upload_file->setPostVar('attachment');
        $upload_file->setTitle('attachment');
        $upload_file->setPath(ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $this->idToDirPath($tmp_name));
        return $upload_file;
    }

    protected function idToDirPath(string $identifier): string
    {
        return self::TEMP_SUB_DIR . '/' . $identifier;
    }

    /**
     * @throws FileNotFoundException
     */
    protected function idToFileMetadata(string $identifier): FileMetadata
    {
        $dir = $this->idToDirPath($identifier);
        foreach ($this->fileSystem->finder()->in([$dir]) as $file) {
            return $file;
        }
        throw new FileNotFoundException();
    }
}
