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
	 * @var int
	 */
	protected $duration;
	/**
	 * @var
	 */
	protected $inputs = array('default');
	/**
	 * @var String
	 */
	protected $rrule;

    /**
     * @var bool
     */
	protected $has_changed = false;

	/**
	 * @param string $event_id
	 */
	public function __construct($event_id = '', $stdClass = null) {
		if ($event_id) {
			$this->setEventId($event_id);
			$this->read($stdClass);
		}
	}


	/**
	 *
	 */
	protected function read($data = null) {
	    if ($data === null) {
            $data = json_decode(xoctRequest::root()->events($this->getEventId())->scheduling()->get()) ?: new stdClass();
        }
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
				return new DateTime($value, new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier()));
			default:
				return $value;
		}
	}

	/**
	 * @return stdClass
	 */
	public function __toStdClass() {
	    $this->getStart()->setTimezone(new DateTimeZone('GMT'));
	    $this->getEnd()->setTimezone(new DateTimeZone('GMT'));

		$stdClass = new stdClass();
		$stdClass->agent_id = $this->getAgentId();
		$stdClass->start = $this->getStart()->format('Y-m-d\TH:i:s\Z');
		if ($this->getEnd()) {
			$stdClass->end = $this->getEnd()->format('Y-m-d\TH:i:s\Z');
		}

		if ($this->getInputs()) {
			$stdClass->inputs = $this->getInputs();
		}

		if ($this->getRrule()) {
			$stdClass->rrule = $this->rrule;

            if ($this->getDuration()) {
                $stdClass->duration = (String) $this->getDuration();
            }
		}

		return $stdClass;
	}


	/**
	 * @return int
	 */
	public function getDuration() {
		return $this->duration;
	}


	/**
	 * @param int $duration
	 */
	public function setDuration($duration) {
//	    if ($this->duration != $duration) {
//	        $this->has_changed = true;
//        }
		$this->duration = $duration;
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
        if ($this->agent_id != $agent_id) {
            $this->has_changed = true;
        }
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
        if ($this->start != $start) {
            $this->has_changed = true;
        }
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
        if ($this->end != $end) {
            $this->has_changed = true;
        }
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
	    if ($this->inputs != $inputs) {
	        $this->has_changed = true;
        }
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

    /**
     * @return bool
     */
    public function hasChanged() {
        return $this->has_changed;
    }

}