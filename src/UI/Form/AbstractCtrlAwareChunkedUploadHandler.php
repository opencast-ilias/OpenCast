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

    protected function readChunkedInformation(): void
    {
        $body = $this->http->request()->getParsedBody();
        $this->_chunk_id = $body['dzuuid'] ?? '';
        $this->_amount_of_chunks = (int) $body['dztotalchunkcount'];
        $this->_chunk_index = (int) $body['dzchunkindex'];
        $this->_chunk_total_size = (int) $body['dztotalfilesize'];
        $this->_is_chunked = $this->_chunk_id !== '';
    }

    public function executeCommand(): void
    {
        $this->readChunkedInformation();
        parent::executeCommand();
    }

    public function supportsChunkedUploads(): bool
    {
        return true;
    }
}
