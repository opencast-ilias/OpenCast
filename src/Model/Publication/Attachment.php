<?php

namespace srag\Plugins\Opencast\Model\Publication;

/**
 * Class attachment
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class Attachment extends publicationMetadata
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
    public function setRef($ref)
    {
        $this->ref = $ref;
    }
}