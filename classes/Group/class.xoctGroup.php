<?php

/**
 * Class xoctGroup
 */
class xoctGroup {

	/**
	 * @param int $id
	 */
	public function __construct($id = 0) {
	}


	/**
	 * @var int
	 */
	public $id = 0;
	/**
	 * @var string
	 */
	public $serie_id;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var int
	 */
	public $status;


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
	 * @return string
	 */
	public function getSerieId() {
		return $this->serie_id;
	}


	/**
	 * @param string $serie_id
	 */
	public function setSerieId($serie_id) {
		$this->serie_id = $serie_id;
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