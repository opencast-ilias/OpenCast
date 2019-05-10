<?php

/**
 * Class xoctSeriesWorkflowParameter
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParameter extends ActiveRecord {

	const TABLE_NAME = 'xoct_series_param';

	const VALUE_IGNORE = xoctWorkflowParameter::VALUE_IGNORE;
	const VALUE_ALWAYS_ACTIVE = xoctWorkflowParameter::VALUE_ALWAYS_ACTIVE;
	const VALUE_ALWAYS_INACTIVE = xoctWorkflowParameter::VALUE_ALWAYS_INACTIVE;
	const VALUE_SHOW_IN_FORM = xoctWorkflowParameter::VALUE_SHOW_IN_FORM;

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @var integer
	 *
	 * @con_sequence        true
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $id;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @db_is_notnull       true
	 */
	protected $obj_id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           250
	 */
	protected $param_id;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @db_is_notnull       true
	 */
	protected $value_member;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @db_is_notnull       true
	 */
	protected $value_admin;


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 *
	 * @return static
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->obj_id;
	}


	/**
	 * @param int $obj_id
	 *
	 * @return static
	 */
	public function setObjId($obj_id) {
		$this->obj_id = $obj_id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getParamId() {
		return $this->param_id;
	}


	/**
	 * @param string $param_id
	 *
	 * @return static
	 */
	public function setParamId($param_id) {
		$this->param_id = $param_id;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getValueMember() {
		return $this->value_member;
	}


	/**
	 * @param int $value_member
	 *
	 * @return static
	 */
	public function setValueMember($value_member) {
		$this->value_member = $value_member;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getValueAdmin() {
		return $this->value_admin;
	}


	/**
	 * @param int $value_admin
	 *
	 * @return static
	 */
	public function setValueAdmin($value_admin) {
		$this->value_admin = $value_admin;

		return $this;
	}
}