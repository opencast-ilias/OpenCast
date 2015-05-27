<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Object/class.xoctObject.php');
/**
 * Class xoctPublication
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPublication extends xoctObject {

	/**
	 * @param string $id
	 */
	public function __construct($id = '') {
		$this->setId($id);
		if ($id) {
			$this->read();
		}
	}


	public function read() {
	}


	/**
	 * @var string
	 */
	protected $id;
	/**
	 * @var string
	 */
	protected $channel;
	/**
	 * @var string
	 */
	protected $mediatype;
	/**
	 * @var string
	 */
	protected $url;
	/**
	 * @var xoctMedia[]
	 */
	protected $media;
	/**
	 * @var xoctAttachment[]
	 */
	protected $attachments;


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
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $id
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