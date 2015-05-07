<?php

/**
 * Class xoctPublicationUsage
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPublicationUsage {

	const USAGE_MP4 = 'mp4';
	const USAGE_ANNOTATION = 'anno';
	const USAGE_MOV = 'mov';
	const MD_TYPE_ATTACHMENT = 1;
	const MD_TYPE_MEDIA = 2;


	/**
	 * @param string $usage
	 */
	public function __construct($usage = '') {
	}


	/**
	 * @var string
	 */
	public $usage = '';
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $publication_id;
	/**
	 * @var int
	 */
	public $status;
	/**
	 * @var string
	 */
	public $media_id;
	/**
	 * @var string
	 */
	public $attachment_id;
	/**
	 * @var int
	 */
	public $md_type = self::MD_TYPE_MEDIA;


	/**
	 * @return string
	 */
	public function getUsage() {
		return $this->usage;
	}


	/**
	 * @param string $usage
	 */
	public function setUsage($usage) {
		$this->usage = $usage;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return string
	 */
	public function getPublicationId() {
		return $this->publication_id;
	}


	/**
	 * @param string $publication_id
	 */
	public function setPublicationId($publication_id) {
		$this->publication_id = $publication_id;
	}


	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}


	/**
	 * @return string
	 */
	public function getMediaId() {
		return $this->media_id;
	}


	/**
	 * @param string $media_id
	 */
	public function setMediaId($media_id) {
		$this->media_id = $media_id;
	}


	/**
	 * @return string
	 */
	public function getAttachmentId() {
		return $this->attachment_id;
	}


	/**
	 * @param string $attachment_id
	 */
	public function setAttachmentId($attachment_id) {
		$this->attachment_id = $attachment_id;
	}


	/**
	 * @return int
	 */
	public function getMdType() {
		return $this->md_type;
	}


	/**
	 * @param int $md_type
	 */
	public function setMdType($md_type) {
		$this->md_type = $md_type;
	}
}