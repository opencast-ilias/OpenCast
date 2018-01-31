<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctPermissionTemplate
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplate extends ActiveRecord {

	/**
	 * @return string
	 */
	public static function returnDbTableName() {
		return 'xoct_perm_template';
	}

	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @con_sequence        true
	 */
	protected $id = 0;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $info;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $role;
	/**
	 * @var Integer
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $read_access;
	/**
	 * @var Integer
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $write_access;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_acl_actions;


	public function getAcls() {
		$acls = array();

		$acl = new xoctAcl();
		$acl->setRole($this->getRole());
		$acl->setAction(xoctAcl::READ);
		$acl->setAllow($this->getRead());
		$acls[] = $acl;

		$acl = new xoctAcl();
		$acl->setRole($this->getRole());
		$acl->setAction(xoctAcl::WRITE);
		$acl->setAllow($this->getWrite());
		$acls[] = $acl;

		foreach (explode(',', $this->getAdditionalAclActions()) as $additional_action) {

		}
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return String
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param String $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return String
	 */
	public function getInfo() {
		return $this->info;
	}


	/**
	 * @param String $info
	 */
	public function setInfo($info) {
		$this->info = $info;
	}


	/**
	 * @return String
	 */
	public function getRole() {
		return $this->role;
	}


	/**
	 * @param String $role
	 */
	public function setRole($role) {
		$this->role = $role;
	}


	/**
	 * @return int
	 */
	public function getRead() {
		return $this->read_access;
	}


	/**
	 * @param int $read
	 */
	public function setRead($read) {
		$this->read_access = $read;
	}


	/**
	 * @return int
	 */
	public function getWrite() {
		return $this->write_access;
	}


	/**
	 * @param int $write
	 */
	public function setWrite($write) {
		$this->write_access = $write;
	}


	/**
	 * @return String
	 */
	public function getAdditionalAclActions() {
		return $this->additional_acl_actions;
	}


	/**
	 * @param String $additional_acl_actions
	 */
	public function setAdditionalAclActions($additional_acl_actions) {
		$this->additional_acl_actions = $additional_acl_actions;
	}

}