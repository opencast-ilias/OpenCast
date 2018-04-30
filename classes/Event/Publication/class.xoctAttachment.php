<?php

/**
 * Class xoctAttachment
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctAttachment extends xoctPublicationMetadata {

	/**
	 * @var string
	 */
	public $ref;


	/**
	 * @return string
	 */
	public function getRef() {
		return $this->ref;
	}


	/**
	 * @param string $ref
	 */
	public function setRef($ref) {
		$this->ref = $ref;
	}
}