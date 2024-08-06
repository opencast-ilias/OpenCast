<?php

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\Refinery\Custom\Constraint;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Factory;
use srag\Plugins\OpenCast\UI\Component\Input\Field\AbstractCtrlAwareChunkedUploadHandler;
use ILIAS\UI\Component\Input\Field\FileUpload;

/**
 * Class ChunkedFile
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ChunkedFile extends File
{
    public $max_file_size;
    protected $chunk_size = 1;

    /**
     * @param mixed $data_factory
     */
    public function __construct(
        DataFactory $data_factory,
        Factory $refinery,
        AbstractCtrlAwareChunkedUploadHandler $handler,
        string $label,
        ?string $byline
    ) {
        global $DIC;
        parent::__construct(
            $DIC->language(),
            $data_factory,
            $refinery,
            $DIC["ui.upload_limit_resolver"],
            $handler,
            $label,
            null,
            $byline
        );
    }

    public static function getInstance(
        AbstractCtrlAwareChunkedUploadHandler $upload_handler,
        string $label,
        string $byline = null
    ): ChunkedFile {
        global $DIC;

        $data_factory = new DataFactory();
        $refinery = new \ILIAS\Refinery\Factory($data_factory, $DIC["lng"]);

        return (new self(
            $data_factory,
            $refinery,
            $upload_handler,
            $label,
            $byline
        ));
    }

    public function withChunkSizeInBytes(int $chunk_size_in_bytes): self
    {
        $clone = clone $this;
        $clone->chunk_size = $chunk_size_in_bytes;
        return $clone;
    }

    public function getChunkSizeInBytes(): int
    {
        return $this->chunk_size;
    }

    public function getMaxFileFize(): int
    {
        return $this->getMaxFileSize();
    }

    public function withMaxFileSize(int $size_in_bytes): FileUpload
    {
        $clone = clone $this;
        $clone->max_file_size = $size_in_bytes;

        return $clone;
    }

    public function getMaxFileSize(): int
    {
        return $this->max_file_size ?? -1;
    }

    protected function isClientSideValueOk($value): bool
    {
        if (is_null($value)) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!is_string($v)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    protected function getConstraintForRequirement(): Constraint
    {
        return $this->refinery->custom()->constraint(
            fn($value): bool => is_array($value) && $value !== [],
            fn($txt, $value) => $txt("msg_no_files_selected")
        );
    }
}
