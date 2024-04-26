<?php

declare(strict_types=1);

namespace srag\Plugins\OpenCast\UI\Component\Input\Field;

use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\ilCtrlAwareUploadHandler;

/**
 * Class ilCtrlAwareUploadHandler
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class AbstractCtrlAwareChunkedUploadHandler extends AbstractCtrlAwareUploadHandler implements ilCtrlAwareUploadHandler
{
    /**
     * @var bool
     */
    protected $_is_chunked = false;
    /**
     * @var int
     */
    protected $_chunk_index = 0;
    /**
     * @var int
     */
    protected $_amount_of_chunks = 0;
    /**
     * @var string|null
     */
    protected $_chunk_id = '';
    /**
     * @var int
     */
    protected $_chunk_total_size = 0;
    /**
     * @var bool
     */
    protected $support_chunked_upload = true;

    protected function readChunkedInformation(): void
    {
        $body = $this->http->request()->getParsedBody();
        $this->_chunk_id = $body['dzuuid'] ?? '';
        // In case we use the chunk file instance for normal upload, the body will contain no chunk data.
        if (isset($body['dztotalchunkcount'])) {
            $this->_amount_of_chunks = (int) $body['dztotalchunkcount'];
        }
        if (isset($body['dzchunkindex'])) {
            $this->_chunk_index = (int) $body['dzchunkindex'];
        }
        if (isset($body['dztotalfilesize'])) {
            $this->_chunk_total_size = (int) $body['dztotalfilesize'];
        }
        $this->_is_chunked = $this->_chunk_id !== '';
    }

    public function executeCommand(): void
    {
        $this->readChunkedInformation();
        parent::executeCommand();
    }

    public function supportsChunkedUploads(): bool
    {
        return $this->support_chunked_upload;
    }

    public function toggleChunkedUploadSupport(bool $state = true): void
    {
        $this->support_chunked_upload = $state;
    }
}
