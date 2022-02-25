<?php

use ILIAS\Data\DataSize;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult;
use srag\Plugins\Opencast\Util\FileTransfer\UploadStorageService;

/**
 * @ilCtrl_IsCalledBy xoctFileUploadHandler: xoctEventGUI, xoctConfGUI
 */
class xoctFileUploadHandler extends AbstractCtrlAwareUploadHandler
{


    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;
    /**
     * @var string
     */
    private $upload_url;
    /**
     * @var string
     */
    private $file_info_url;
    /**
     * @var string
     */
    private $file_removal_url;

    public function __construct(UploadStorageService $uploadStorageService,
                                string               $upload_url = '',
                                string               $file_info_url = '',
                                string               $file_removal_url = '')
    {
        parent::__construct();
        $this->uploadStorageService = $uploadStorageService;
        $this->upload_url = $upload_url;
        $this->file_info_url = $file_info_url;
        $this->file_removal_url = $file_removal_url;
    }

    public function getUploadURL(): string
    {
        return $this->upload_url ?: $this->ctrl->getLinkTargetByClass([static::class], self::CMD_UPLOAD);
    }

    public function getExistingFileInfoURL(): string
    {
        return $this->file_info_url ?: $this->ctrl->getLinkTargetByClass([static::class], self::CMD_INFO);
    }

    public function getFileRemovalURL(): string
    {
        return $this->file_removal_url ?: $this->ctrl->getLinkTargetByClass([static::class], self::CMD_REMOVE);
    }


    /**
     * @throws IllegalStateException
     */
    protected function getUploadResult(): HandlerResult
    {
        $this->upload->process();
        $array = $this->upload->getResults();
        $result = end($array);

        if ($result instanceof UploadResult && $result->isOK()) {
            $identifier = $this->uploadStorageService->moveUploadToStorage($result);
            $status = HandlerResult::STATUS_OK;
            $message = 'Upload ok';
        } else {
            $status = HandlerResult::STATUS_FAILED;
            $identifier = '';
            $message = $result->getStatus()->getMessage();
        }

        return new BasicHandlerResult($this->getFileIdentifierParameterName(), $status, $identifier, $message);
    }

    protected function getRemoveResult(string $identifier): HandlerResult
    {
        try {
            $this->uploadStorageService->delete($identifier);
            $status = HandlerResult::STATUS_OK;
            $message = 'File Deleted';
        } catch (FileNotFoundException $e) {
            $status = HandlerResult::STATUS_FAILED;
            $message = "File not found";
        }

        return new BasicHandlerResult($this->getFileIdentifierParameterName(), $status, $identifier, $message);
    }

    protected function getInfoResult(string $identifier): FileInfoResult
    {
        $info = $this->uploadStorageService->getFileInfo($identifier);
        /** @var DataSize $size */
        $size = $info['size'];
        return new BasicFileInfoResult(
            $this->getFileIdentifierParameterName(),
            $identifier,
            $info['name'],
            $size->getSize(),
            $info['mimeType']
        );
    }

    public function getInfoForExistingFiles(array $file_ids): array
    {
        $infos = [];
        foreach (array_filter($file_ids) as $file_id) {
            $infos[] = $this->getInfoResult($file_id);
        }

        return $infos;
    }

    /**
     * @return UploadStorageService
     */
    public function getUploadStorageService(): UploadStorageService
    {
        return $this->uploadStorageService;
    }
}