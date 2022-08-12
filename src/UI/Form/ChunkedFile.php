<?php

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\UI\Implementation\Component\Input\Field\File;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Factory;
use srag\Plugins\OpenCast\UI\Component\Input\Field\AbstractCtrlAwareChunkedUploadHandler;

/**
 * Class ChunkedFile
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ChunkedFile extends File
{
    /**
     * @var int
     */
    private $max_chunk_size = -1;
    private $chunked = false;
    
    public function __construct(
        DataFactory $data_factory,
        Factory $refinery,
        AbstractCtrlAwareChunkedUploadHandler $handler,
        $label,
        $byline
    ) {
        parent::__construct($data_factory, $refinery, $handler, $label, $byline);
    }
    
    public static function getInstance(
        AbstractCtrlAwareChunkedUploadHandler $upload_handler,
        string $label,
        string $byline = null
    ) : ChunkedFile {
        global $DIC;
        
        $data_factory = new \ILIAS\Data\Factory();
        $refinery = new \ILIAS\Refinery\Factory($data_factory, $DIC["lng"]);
        
        return (new self(
            $data_factory,
            $refinery,
            $upload_handler,
            $label,
            $byline
        ));
    }
    
    public function withChunkedUpload(bool $chunked) : \ILIAS\UI\Component\Input\Field\File
    {
        $clone = clone $this;
        $clone->chunked = $chunked;
        
        return $clone;
    }
    
    public function isChunkedUpload() : bool
    {
        return $this->chunked;
    }
}
