<?php

/**
 * Class xoctUserViewType
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctUserViewType extends ActiveRecord {

	const TABLE_NAME = 'xoct_user_view_type';

	const VIEW_TYPE_LIST = 0;
	const VIEW_TYPE_TILES = 1;

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}

	/**
	 * @param $user_id
	 * @param $ref_id
	 * @param $view_type
	 */
	public static function changeViewType($user_id, $ref_id, $view_type) {
		$self = self::where(['ref_id' => $ref_id, 'user_id' => $user_id])->first();
		$self = $self ?: new self();
		$self->setUserId($user_id)
			->setRefId($ref_id)
			->setViewType($view_type)
			->store();
	}

	/**
	 * @param $user_id
	 * @param $ref_id
	 * @return int
	 */
	public static function getViewTypeForUser($user_id, $ref_id) {
		if (!xoct::isIlias54()) {
			return self::VIEW_TYPE_LIST;
		}
		/** @var self $self */
		$self = self::where(['user_id' => $user_id, 'ref_id' => $ref_id])->first();
		/** @var xoctOpenCast $xoctOpenCast */
		$xoctOpenCast = xoctOpenCast::find(ilObjOpenCast::_lookupObjectId($ref_id));
		if (!$xoctOpenCast->isViewChangeable() || !$self) {
			return $xoctOpenCast->getDefaultView();
		}

		return $self->getViewType();
	}

	/**
	 * @var integer
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_is_primary    true
	 * @con_sequence   true
	 * @con_is_notnull true
	 */
	protected $id;
	/**
	 * @var integer
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_is_notnull true
	 */
	protected $ref_id;
	/**
	 * @var integer
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_is_notnull true
	 */
	protected $user_id;
	/**
	 * @var integer
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_is_notnull true
	 */
	protected $view_type = self::VIEW_TYPE_LIST;

	/**
	 * @return int
	 */
	public function getRefId() {
		return $this->ref_id;
	}

	/**
	 * @param int $ref_id
	 * @return xoctUserViewType
	 */
	public function setRefId($ref_id) {
		$this->ref_id = $ref_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId() {
		return $this->user_id;
	}

	/**
	 * @param int $user_id
	 * @return xoctUserViewType
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getViewType() {
		return $this->view_type;
	}

	/**
	 * @param int $view_type
	 * @return xoctUserViewType
	 */
	public function setViewType($view_type) {
		$this->view_type = $view_type;
		return $this;
	}
}