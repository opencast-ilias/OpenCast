<?php

/**
 * Class xoctUserViewType
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctUserSetting extends ActiveRecord {

	const TABLE_NAME = 'xoct_user_setting';

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
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
	 * @var string
	 *
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     56
	 * @con_is_notnull true
	 */
	protected $name;
	/**
	 * @var integer
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_is_notnull true
	 */
	protected $value;

	/**
	 * @return int
	 */
	public function getRefId() {
		return $this->ref_id;
	}

	/**
	 * @param int $ref_id
	 * @return xoctUserSetting
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
	 * @return xoctUserSetting
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param int $value
	 * @return xoctUserSetting
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return xoctUserSetting
	 */
	public function setName(string $name) {
		$this->name = $name;
		return $this;
	}


}