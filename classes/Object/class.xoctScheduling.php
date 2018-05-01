<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctScheduling
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctScheduling extends xoctObject {

	/**
	 * @var
	 */
	protected $event_id;
	/**
	 * @var
	 */
	protected $agent_id;
	/**
	 * @var DateTime
	 */
	protected $start;
	/**
	 * @var DateTime
	 */
	protected $end;
	/**
	 * @var
	 */
	protected $inputs;
	/**
	 * @var String
	 */
	protected $rrule;

	/**
	 * @param string $event_id
	 */
	public function __construct($event_id = '') {
		if ($event_id) {
			$this->setEventId($event_id);
			$this->read();
		}
	}


	/**
	 *
	 */
	protected function read() {
		$data = json_decode(xoctRequest::root()->events($this->getEventId())->scheduling()->get());
		$this->loadFromStdClass($data);
	}


	/**
	 * @param $fieldname
	 * @param $value
	 *
	 * @return mixed
	 */
	protected function wakeup($fieldname, $value) {
		switch ($fieldname) {
			case 'start':
			case 'end':
				return new DateTime($value);
			default:
				return $value;
		}
	}

	/**
	 * @return stdClass
	 */
	public function __toStdClass() {
		$stdClass = new stdClass();
		$stdClass->agent_id = $this->getAgentId();
		$stdClass->start = $this->getStart()->format('Y-m-d\TH:i:s\Z');
		$stdClass->end = $this->getEnd()->format('Y-m-d\TH:i:s\Z');
		$stdClass->inputs = array("default");

		if ($this->rrule) {
			$stdClass->rrule = $this->rrule;
		}

		return $stdClass;
	}


	/**
	 * @return mixed
	 */
	public function getEventId() {
		return $this->event_id;
	}


	/**
	 * @param mixed $event_id
	 */
	public function setEventId($event_id) {
		$this->event_id = $event_id;
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
	 * @return DateTime
	 */
	public function getStart() {
		return $this->start;
	}


	/**
	 * @param DateTime $start
	 */
	public function setStart($start) {
		$this->start = $start;
	}


	/**
	 * @return DateTime
	 */
	public function getEnd() {
		return $this->end;
	}


	/**
	 * @param DateTime $end
	 */
	public function setEnd($end) {
		$this->end = $end;
	}


	/**
	 * @return mixed
	 */
	public function getInputs() {
		return $this->inputs;
	}


	/**
	 * @param mixed $inputs
	 */
	public function setInputs($inputs) {
		$this->inputs = $inputs;
	}


	/**
	 * @return String
	 */
	public function getRrule() {
		return $this->rrule;
	}


	/**
	 * @param String $rrule
	 */
	public function setRRule($rrule) {
		$this->rrule = $rrule;
	}


}