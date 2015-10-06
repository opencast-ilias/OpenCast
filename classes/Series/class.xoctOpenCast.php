<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');
require_once('class.xoctSeries.php');
/**
 * Class xoctOpenCast
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctOpenCast extends ActiveRecord {

	/**
	 * @return string
	 * @deprecated
	 */
	static function returnDbTableName() {
		return 'xoct_data';
	}


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return 'xoct_data';
	}


	/**
	 * @param $series_identifier
	 *
	 * @return int
	 */
	public static function lookupObjId($series_identifier) {
		$xoctOpenCast = xoctOpenCast::where(array( 'series_identifier' => $series_identifier ))->last();
		if ($xoctOpenCast instanceof xoctOpenCast) {
			return $xoctOpenCast->getObjId();
		}

		return false;
	}


	/**
	 * @return xoctSeries
	 */
	public function getSeries() {
		/**
		 * @var $xoctSeries xoctSeries
		 */
		if ($this->getSeriesIdentifier()) {
			$xoctSeries = xoctSeries::find($this->getSeriesIdentifier());
			if ($xoctSeries instanceof xoctSeries) {
				return $xoctSeries;
			}
		}

		return new xoctSeries();
	}


	public function create() {
		if ($this->getObjId() === 0) {
			$this->update();
		} else {
			parent::create();
		}
	}


	/**
	 * @var
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_is_notnull true
	 * @con_is_primary true
	 * @con_is_unique  true
	 */
	protected $obj_id;
	/**
	 * @var string
	 *
	 * @con_has_field true
	 * @con_fieldtype text
	 * @con_length    256
	 */
	protected $series_identifier;
	/**
	 * @var string
	 *
	 * @con_has_field true
	 * @con_fieldtype text
	 * @con_length    4000
	 */
	protected $intro_text;
	/**
	 * @var
	 *
	 * @con_has_field true
	 * @con_fieldtype integer
	 * @con_length    1
	 */
	protected $use_annotations = false;
	/**
	 * @var
	 *
	 * @con_has_field true
	 * @con_fieldtype integer
	 * @con_length    1
	 */
	protected $streaming_only = false;
	/**
	 * @var
	 *
	 * @con_has_field true
	 * @con_fieldtype integer
	 * @con_length    1
	 */
	protected $permission_per_clip = false;
	/**
	 * @var
	 *
	 * @con_has_field true
	 * @con_fieldtype integer
	 * @con_length    1
	 */
	protected $permission_allow_set_own = false;
	/**
	 * @var
	 *
	 * @con_has_field true
	 * @con_fieldtype integer
	 * @con_length    1
	 */
	protected $agreement_accepted = false;
	/**
	 * @var bool
	 *
	 * @con_has_field true
	 * @con_fieldtype integer
	 * @con_length    1
	 */
	protected $obj_online = false;


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->obj_id;
	}


	/**
	 * @param int $obj_id
	 */
	public function setObjId($obj_id) {
		$this->obj_id = $obj_id;
	}


	/**
	 * @return mixed
	 */
	public function getSeriesIdentifier() {
		return $this->series_identifier;
	}


	/**
	 * @param mixed $series_identifier
	 */
	public function setSeriesIdentifier($series_identifier) {
		$this->series_identifier = $series_identifier;
	}


	/**
	 * @return mixed
	 */
	public function getUseAnnotations() {
		return $this->use_annotations;
	}


	/**
	 * @param mixed $use_annotations
	 */
	public function setUseAnnotations($use_annotations) {
		$this->use_annotations = $use_annotations;
	}


	/**
	 * @return mixed
	 */
	public function getStreamingOnly() {
		return $this->streaming_only;
	}


	/**
	 * @param mixed $streaming_only
	 */
	public function setStreamingOnly($streaming_only) {
		$this->streaming_only = $streaming_only;
	}


	/**
	 * @return mixed
	 */
	public function getPermissionPerClip() {
		return $this->permission_per_clip;
	}


	/**
	 * @param mixed $permission_per_clip
	 */
	public function setPermissionPerClip($permission_per_clip) {
		$this->permission_per_clip = $permission_per_clip;
	}


	/**
	 * @return mixed
	 */
	public function getAgreementAccepted() {
		return $this->agreement_accepted;
	}


	/**
	 * @param mixed $agreement_accepted
	 */
	public function setAgreementAccepted($agreement_accepted) {
		$this->agreement_accepted = $agreement_accepted;
	}


	/**
	 * @return boolean
	 */
	public function isObjOnline() {
		return $this->obj_online;
	}


	/**
	 * @param boolean $obj_online
	 */
	public function setObjOnline($obj_online) {
		$this->obj_online = $obj_online;
	}


	/**
	 * @return string
	 */
	public function getIntroText() {
		return $this->intro_text;
	}


	/**
	 * @param string $intro_text
	 */
	public function setIntroText($intro_text) {
		$this->intro_text = $intro_text;
	}


	/**
	 * @return bool
	 */
	public function getPermissionAllowSetOwn() {
		return ($this->permission_allow_set_own && $this->getPermissionPerClip());
	}


	/**
	 * @param mixed $permission_allow_set_own
	 */
	public function setPermissionAllowSetOwn($permission_allow_set_own) {
		$this->permission_allow_set_own = $permission_allow_set_own;
	}
}

?>
