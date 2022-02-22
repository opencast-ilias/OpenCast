<?php

namespace srag\Plugins\Opencast\Util\FileTransfer;

use ILIAS\Data\DataSize;
use ILIAS\DI\Container;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Location;
use ilUtil;
use ilWACSignedPath;

class PaellaConfigStorageService extends UploadStorageService
{
    /**
     * @var Container
     */
    protected $dic;

    /**
     * @param string $url
     * @return string identifier
     */
    public function fetchFromUrlAndStore(string $url) : string
    {
        $identifier = uniqid();
        $content = file_get_contents($url);
        if (json_decode($content) === null) {
            // todo: invalid json
        }
        $this->fileSystem->write($this->idToDirPath($identifier) . DIRECTORY_SEPARATOR . 'config.json',
            $content);
        // todo
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            $exists = false;
        }
    }

    /**
     * @param UploadResult $uploadResult
     * @return string identifier
     */
    public function moveUploadToStorage(UploadResult $uploadResult) : string
    {
        $identifier = uniqid();
        $this->fileUpload->moveOneFileTo($uploadResult, $this->idToDirPath($identifier), Location::WEB);
        return $identifier;
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
        return [
            'size' => $this->fileSystem->getSize($metadata->getPath(), $fileSizeUnit),
            'name' => pathinfo($metadata->getPath(), PATHINFO_BASENAME),
            'mimeType' => $this->fileSystem->getMimeType($metadata->getPath())
        ];
    }

    public function getWACSignedPath(string $identifier) : string
    {
        // ilUtil::getWebspaceDir is deprecated, but I didn't find out how else to get an absolute path, which we need
        // for the paella player
        return ilWACSignedPath::signFile(ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR
            . $this->idToFileMetadata($identifier)->getPath());
    }

    public function getFileAsBase64(string $identifier) : string
    {
        $contents = $this->fileSystem->read($this->idToFileMetadata($identifier)->getPath());
        return base64_encode($contents);
    }

    public function exists(string $file_id) : bool
    {
        return $this->fileSystem->hasDir($this->idToDirPath($file_id));
    }
}