<?php

namespace ILIAS\UI\Implementation\Component\Input\Field;

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
    ): ChunkedFile {
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

    protected function getConstraintForRequirement(): \ILIAS\Refinery\Custom\Constraint
    {
        return $this->refinery->custom()->constraint(
            function ($value): bool {
                return (is_array($value) && $value !== []);
            },
            function ($txt, $value) {
                return $txt("msg_no_files_selected");
            }
        );
    }
}
