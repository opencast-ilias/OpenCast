<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');

/**
 * Class xoctPublicationUsage
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPublicationUsage extends ActiveRecord {

	/**
	 * @var array
	 */
	protected static $usage_ids = array(
		self::USAGE_ANNOTATION,
		self::USAGE_MP4,
		self::USAGE_MOV,
		self::USAGE_PREVIEW,
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


	const USAGE_MP4 = 'mp4';
	const USAGE_ANNOTATION = 'annotations';
	const USAGE_MOV = 'mov';
	const USAGE_PREVIEW = 'preview';
	const MD_TYPE_ATTACHMENT = 1;
	const MD_TYPE_MEDIA = 2;
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
	protected $publication_id;
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
	protected $ext_id;
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
	public function getExtId() {
		return $this->ext_id;
	}


	/**
	 * @param string $ext_id
	 */
	public function setExtId($ext_id) {
		$this->ext_id = $ext_id;
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