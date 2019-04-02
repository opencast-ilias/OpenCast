<?php

/**
 * Class xoctWorkflowParameter
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameter extends ActiveRecord {

	const TABLE_NAME = 'xoct_workflow_param';

	const VALUE_IGNORE = 0;
	const VALUE_SET_AUTOMATICALLY = 1;
	const VALUE_SHOW_IN_FORM = 2;

	const TYPE_CHECKBOX = 'checkbox';

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}

	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           250
	 */
	protected $id;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_is_notnull       true
	 * @db_length           256
	 */
	protected $title;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $type;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $default_value_member = self::VALUE_IGNORE;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $default_value_admin = self::VALUE_IGNORE;


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
	public function getType() {
		return $this->type;
	}


	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}


	/**
	 * @return integer
	 */
	public function getDefaultValueMember() {
		return $this->default_value_member;
	}


	/**
	 * @param integer $default_value_member
	 */
	public function setDefaultValueMember($default_value_member) {
		$this->default_value_member = $default_value_member;
	}


	/**
	 * @return int
	 */
	public function getDefaultValueAdmin() {
		return $this->default_value_admin;
	}


	/**
	 * @param int $default_value_admin
	 */
	public function setDefaultValueAdmin($default_value_admin) {
		$this->default_value_admin = $default_value_admin;
	}
}