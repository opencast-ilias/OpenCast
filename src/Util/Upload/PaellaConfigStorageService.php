<?php

namespace srag\Plugins\Opencast\Util\Upload;

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
            'name' => pathinfo($metadata->getPath(), PATHINFO_FILENAME),
            'mimeType' => $this->fileSystem->getMimeType($metadata->getPath())
        ];
    }

    public function getWACSignedPath(string $identifier) : string
    {
        // ilUtil::getWebspaceDir is deprecated, but I didn't find out how else to get an absolute path, which we need
        // for the paella player
        return ilWACSignedPath::signFile(ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR . $this->idToFileMetadata($identifier)->getPath());
    }
//
//    public function fetchConfigFromUrlAndStore(string $url, string $series_identifier, bool $live = false) : string
//    {
//        $config_as_string = file_get_contents($url);
//        $path = $this->createAndGetPath($series_identifier);
//        $path .= $live ? "config_live.json" : "config.json";
//        $this->deleteOldFile($path);
//        $stream = Streams::ofString($config_as_string);
//        $web = $this->dic->filesystem()->web();
//        $web->writeStream($path, $stream);
//        return $path;
//    }
//
//    protected function createAndGetPath(string $series_identifier) : string
//    {
//        $path = self::BASEPATH . 'custom_config' . DIRECTORY_SEPARATOR . $series_identifier . DIRECTORY_SEPARATOR;
//        if (!$this->dic->filesystem()->web()->hasDir($path)) {
//            $this->dic->filesystem()->web()->createDir($path);
//        }
//
//        return $path;
//    }
//
//    protected function deleteOldFile(string $path)
//    {
//        $filesystem = $this->dic->filesystem()->web();
//        if ($filesystem->has($path)) {
//            $filesystem->delete($path);
//        }
//
//    }
}