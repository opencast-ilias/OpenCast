<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication;

/**
 * Class attachment
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class Attachment extends PublicationMetadata
{
    /**
     * @var string
     */
    public $ref;

    /**
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     */
    public function setRef($ref): void
    {
        $this->ref = $ref;
    }
}
