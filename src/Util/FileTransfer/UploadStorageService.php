<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\FileTransfer;

use ILIAS\Data\DataSize;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Filesystem;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use xoctUploadFile;
use srag\Plugins\Opencast\Util\MimeType as MimeTypeUtil;

class UploadStorageService
{
    public const TEMP_SUB_DIR = 'opencast';

    public function __construct(protected Filesystem $fileSystem, protected FileUpload $fileUpload)
    {
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

        $source = fopen($uploadResult->getPath(), 'rb');
        if ($source === false) {
            throw new IOException('Could not open uploaded chunk for reading.');
        }

        try {
            if ($this->fileSystem->has($path)) {
                // Subsequent chunks: append directly to the existing file via a
                // stream copy. file_get_contents() would load the whole chunk
                // (up to ~90% of the PHP upload limit) into memory and exhaust
                // memory_limit on large uploads, which broke multi-chunk uploads.
                $target = fopen(ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $path, 'ab');
                if ($target === false) {
                    throw new IOException('Could not open chunk storage for appending.');
                }
                try {
                    stream_copy_to_stream($source, $target);
                } finally {
                    fclose($target);
                }
            } else {
                // First chunk: let the filesystem create the directory and the
                // file with a memory-safe stream write.
                $this->fileSystem->writeStream($path, Streams::ofResource($source));
            }
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
        }

        return $chunk_id;
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function delete(string $identifier): void
    {
        if ($identifier === '') {
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
        $folders = $folders->sort(fn($a, $b): int => strlen((string) $a->getPath()) - strlen((string) $b->getPath()));
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
            } catch (\Throwable) {
                $folders->next();
            }
        }
        try {
            $this->fileSystem->deleteDir($dir);
        } catch (\Throwable) {
        }
    }

    /**
     * @return array{path: string, size: DataSize, name: string, mimeType: string}
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function getFileInfo(string $identifier, int $fileSizeUnit = DataSize::Byte): array
    {
        // An empty identifier would resolve to the temp root (opencast/) and
        // return an arbitrary, unrelated upload directory, which then breaks the
        // mime-type lookup (see #535, #536). Refuse it explicitly.
        if ($identifier === '') {
            throw new FileNotFoundException('Empty upload identifier.');
        }
        $metadata = $this->idToFileMetadata($identifier);
        /** TODO: path is hard coded here because it's required to send the file via curlFile and I didn't find a way to get the path dynamically from the file service */
        try {
            $data_size = $this->fileSystem->getSize($metadata->getPath(), $fileSizeUnit);
        } catch (\Throwable) {
            $data_size = new DataSize(0, $fileSizeUnit);
        }

        return [
            'path' => ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $metadata->getPath(),
            'size' => $data_size,
            'name' => pathinfo((string) $metadata->getPath(), PATHINFO_FILENAME),
            'mimeType' => $this->fileSystem->getMimeType($metadata->getPath()),
            'id' => $identifier
        ];
    }

    public function buildACLUploadFile(ACL $acl, string $media_package_id): xoctUploadFile
    {
        $tmp_name = uniqid('tmp', false) . '.xml';
        $this->fileSystem->write($this->idToDirPath($tmp_name), (new ACLtoXML($acl))->getXML($media_package_id));
        $upload_file = new xoctUploadFile();
        $upload_file->setFileSize(
            $this->fileSystem->getSize($this->idToDirPath($tmp_name), DataSize::Byte)->getSize()
        );
        $upload_file->setMimeType(MimeTypeUtil::APPLICATION__XML);
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
    protected function idToFileMetadata(string $identifier)
    {
        $dir = $this->idToDirPath($identifier);
        // Restrict to files: a bare directory would be passed on to
        // getMimeType() and fail with a misleading "file not found" (see #535, #536).
        foreach ($this->fileSystem->finder()->in([$dir])->files() as $file) {
            return $file;
        }
        throw new FileNotFoundException();
    }
}
