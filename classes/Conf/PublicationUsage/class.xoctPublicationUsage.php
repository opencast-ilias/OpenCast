<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');

/**
 * Class xoctPublicationUsage
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPublicationUsage extends ActiveRecord {

	const USAGE_ANNOTATE = 'annotate';
	const USAGE_PLAYER = 'player';
	const USAGE_API = 'api';
	const USAGE_THUMBNAIL = 'thumbnail';
	const USAGE_THUMBNAIL_FALLBACK = 'thumbnail_fallback';
	const MD_TYPE_ATTACHMENT = 1;
	const MD_TYPE_MEDIA = 2;
	const MD_TYPE_PUBLICATION_ITSELF = 0;
	/**
	 * @var array
	 */
	protected static $usage_ids = array(
		self::USAGE_ANNOTATE,
		self::USAGE_PLAYER,
		self::USAGE_API,
		self::USAGE_THUMBNAIL,
		self::USAGE_THUMBNAIL_FALLBACK,
	);


	/**
	 * @param $usage
	 *
	 * @return xoctPublicationUsage
	 */
	public static function getUsage($usage) {
		return self::find($usage);
	}


	/**
	 * @return array
	 */
	public static function getMissingUsageIds() {
		$missing = array_diff(self::$usage_ids, self::getArray(NULL, 'usage_id'));

		return $missing;
	}


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 * @deprecated
	 */
	static function returnDbTableName() {
		return 'xoct_publication_usage';
	}


	/**
	 * @var string
	 *
	 * @con_is_primary true
	 * @con_is_unique  true
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     512
	 */
	protected $usage_id = '';
	/**
	 * @var string
	 *
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     512
	 */
	protected $title;
	/**
	 * @var string
	 *
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     4000
	 */
	protected $description;
	/**
	 * @var string
	 *
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     512
	 */
	protected $channel;
	/**
	 * @var bool
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     1
	 */
	protected $status;
	/**
	 * @var string
	 *
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     512
	 */
	protected $flavor;
	/**
	 * @var int
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     1
	 */
	protected $md_type = NULL;


	/**
	 * @return string
	 */
	public function getUsageId() {
		return $this->usage_id;
	}


	/**
	 * @param string $usage_id
	 */
	public function setUsageId($usage_id) {
		$this->usage_id = $usage_id;
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
	 * @return boolean
	 */
	public function isStatus() {
		return $this->status;
	}


	/**
	 * @param boolean $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}


	/**
	 * @return string
	 */
	public function getFlavor() {
		return $this->flavor;
	}


	/**
	 * @param string $flavor
	 */
	public function setFlavor($flavor) {
		$this->flavor = $flavor;
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