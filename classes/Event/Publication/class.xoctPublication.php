<?php

/**
 * Class xoctPublication
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPublication {

	/**
	 * @param string $id
	 */
	public function __construct($id = '') {
	}


	/**
	 * @var void
	 */
	public $id;
	/**
	 * @var string
	 */
	public $channel;
	/**
	 * @var string
	 */
	public $mediatype;
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var xoctMedia[]
	 */
	public $media;
	/**
	 * @var xoctAttachment[]
	 */
	public $attachments;


	/**
	 * @return void
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param void $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return string
	 */
	public function getChannel() {
		return $this->channel;
	}


	/**
	 * @param string $channel
	 */
	public function setChannel($channel) {
		$this->channel = $channel;
	}


	/**
	 * @return string
	 */
	public function getMediatype() {
		return $this->mediatype;
	}


	/**
	 * @param string $mediatype
	 */
	public function setMediatype($mediatype) {
		$this->mediatype = $mediatype;
	}


	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}


	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}


	/**
	 * @return xoctMedia[]
	 */
	public function getMedia() {
		return $this->media;
	}


	/**
	 * @param xoctMedia[] $media
	 */
	public function setMedia($media) {
		$this->media = $media;
	}


	/**
	 * @return xoctAttachment[]
	 */
	public function getAttachments() {
		return $this->attachments;
	}


	/**
	 * @param xoctAttachment[] $attachments
	 */
	public function setAttachments($attachments) {
		$this->attachments = $attachments;
	}
}