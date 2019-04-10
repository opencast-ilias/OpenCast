<?php

/**
 * Class xoctWorkflowParameter
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameter extends ActiveRecord {

	const TABLE_NAME = 'xoct_workflow_param';

	const VALUE_IGNORE = 0;
	const VALUE_ALWAYS_ACTIVE = 1;
	const VALUE_ALWAYS_INACTIVE = 2;
	const VALUE_SHOW_IN_FORM = 3;

	const TYPE_CHECKBOX = 'checkbox';

	public static $possible_values = [
		self::VALUE_IGNORE,
		self::VALUE_ALWAYS_ACTIVE,
		self::VALUE_ALWAYS_INACTIVE,
		self::VALUE_SHOW_IN_FORM
	];

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @return $this
	 */
	public function create() {
		parent::create();
		return $this;
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
	 * @param $id
	 *
	 * @return $this
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param $title
	 *
	 * @return $this
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param $type
	 *
	 * @return $this
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}


	/**
	 * @return integer
	 */
	public function getDefaultValueMember() {
		return $this->default_value_member;
	}


	/**
	 * @param integer $default_value_member
	 *
	 * @return xoctWorkflowParameter
	 */
	public function setDefaultValueMember($default_value_member) {
		$this->default_value_member = $default_value_member;
		return $this;
	}


	/**
	 * @return int
	 */
	public function getDefaultValueAdmin() {
		return $this->default_value_admin;
	}


	/**
	 * @param int $default_value_admin
	 *
	 * @return xoctWorkflowParameter
	 */
	public function setDefaultValueAdmin($default_value_admin) {
		$this->default_value_admin = $default_value_admin;
		return $this;
	}


}