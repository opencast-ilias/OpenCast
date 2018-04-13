<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctAgent
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctAgent extends xoctObject {

	/**
	 * @var
	 */
	protected $agent_id;
	/**
	 * @var array
	 */
	protected $inputs = array();
	/**
	 * @var
	 */
	protected $update;
	/**
	 * @var
	 */
	protected $url;
	/**
	 * @var
	 */
	protected $status;


	/**
	 * @return array
	 */
	public static function getAllAgents() {
		$data = json_decode(xoctRequest::root()->agents()->get());

		foreach ($data as $d) {
			$xoctAgent = self::findOrLoadFromStdClass($d->agent_id, $d);
			$return[] = $xoctAgent;
		}

		return $return;
	}


	/**
	 * @return mixed
	 */
	public function getAgentId() {
		return $this->agent_id;
	}


	/**
	 * @param mixed $agent_id
	 */
	public function setAgentId($agent_id) {
		$this->agent_id = $agent_id;
	}


	/**
	 * @return array
	 */
	public function getInputs() {
		return $this->inputs;
	}


	/**
	 * @param array $inputs
	 */
	public function setInputs($inputs) {
		$this->inputs = $inputs;
	}


	/**
	 * @return mixed
	 */
	public function getUpdate() {
		return $this->update;
	}


	/**
	 * @param mixed $update
	 */
	public function setUpdate($update) {
		$this->update = $update;
	}


	/**
	 * @return mixed
	 */
	public function getUrl() {
		return $this->url;
	}


	/**
	 * @param mixed $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}


	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param mixed $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}


}