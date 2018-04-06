<?php

/**
 * Class xoctEventAdditions
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEventAdditions extends ActiveRecord {

	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 * @deprecated
	 */
	static function returnDbTableName() {
		return 'xoct_event_additions';
	}


	public function update() {
		if (!$this->getId()) {
			return false;
		}
		if (!self::where(array( 'id' => $this->getId() ))->hasSets()) {
			$this->create();
		} else {
			xoctEvent::removeFromCache($this->getId());
			parent::update();
		}
	}


	public function create() {
		if (!$this->getId()) {
			return false;
		}
		xoctEvent::removeFromCache($this->getId());
		parent::create();
	}


	/**
	 * @var string
	 *
	 * @description Unique identifier from opencast
	 *
	 * @con_is_primary true
	 * @con_is_unique  true
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     64
	 */
	protected $id;
	/**
	 * @var bool
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     1
	 */
	protected $is_online = true;


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
	 * @return boolean
	 */
	public function getIsOnline() {
		return $this->is_online;
	}


	/**
	 * @param boolean $is_online
	 */
	public function setIsOnline($is_online) {
		$this->is_online = $is_online;
	}
}
