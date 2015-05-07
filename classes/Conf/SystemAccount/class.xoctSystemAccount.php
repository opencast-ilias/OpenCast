<?php

/**
 * Class xoctSystemAccount
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctSystemAccount {

	/**
	 * @param string $domain
	 */
	public function __construct($domain = '') {
	}


	/**
	 * @var string
	 */
	public $domain = '';
	/**
	 * @var string
	 */
	public $ext_id;
	/**
	 * @var int
	 */
	public $status;


	/**
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}


	/**
	 * @param string $domain
	 */
	public function setDomain($domain) {
		$this->domain = $domain;
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
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}
}