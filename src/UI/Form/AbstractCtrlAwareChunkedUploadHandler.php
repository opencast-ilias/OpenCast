<?php declare(strict_types=1);

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
    protected $is_chunked = false;
    protected $chunk_index = 0;
    protected $amount_of_chunks = 0;
    protected $chunk_id = '';
    protected $chunk_total_size = 0;
    
    protected function readChunkedInformation() : void
    {
        $body = $this->http->request()->getParsedBody();
        $this->chunk_id = $body['dzuuid'] ?? '';
        $this->amount_of_chunks = (int) $body['dztotalchunkcount'];
        $this->chunk_index = (int) $body['dzchunkindex'];
        $this->chunk_total_size = (int) $body['dztotalfilesize'];
        $this->is_chunked = $this->chunk_id !== '';
    }
    
    public function executeCommand() : void
    {
        $this->readChunkedInformation();
        parent::executeCommand();
    }
    
    public function supportsChunkedUploads() : bool
    {
        return true;
    }
    
}
