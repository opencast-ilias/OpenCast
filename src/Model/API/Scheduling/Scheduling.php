<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace srag\Plugins\Opencast\Model\API\Scheduling;

use DateTime;
use DateTimeZone;
use Exception;
use ilTimeZone;
use ilTimeZoneException;
use srag\Plugins\Opencast\Model\API\APIObject;
use stdClass;
use xoctException;
use xoctRequest;

/**
 * Class xoctScheduling
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Scheduling extends APIObject
{

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
     * @param string   $event_id
     * @param stdClass $stdClass
     *
     * @throws xoctException
     */
    public function __construct(string $event_id = '', stdClass $stdClass = null)
    {
        if ($event_id) {
            $this->setEventId($event_id);
            $this->read($stdClass);
        }
    }


    /**
     * @param stdClass $data
     *
     * @throws xoctException
     */
    protected function read(stdClass $data = null)
    {
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
     * @throws ilTimeZoneException
     * @throws Exception
     */
    protected function wakeup($fieldname, $value)
    {
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
    public function __toStdClass() : stdClass
    {
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
                $stdClass->duration = (string) $this->getDuration();
            }
        }

        return $stdClass;
    }


    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }


    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        //	    if ($this->duration != $duration) {
        //	        $this->has_changed = true;
        //        }
        $this->duration = $duration;
    }


    /**
     * @return mixed
     */
    public function getEventId()
    {
        return $this->event_id;
    }


    /**
     * @param string $event_id
     */
    public function setEventId(string $event_id)
    {
        $this->event_id = $event_id;
    }


    /**
     * @return mixed
     */
    public function getAgentId()
    {
        return $this->agent_id;
    }


    /**
     * @param mixed $agent_id
     */
    public function setAgentId($agent_id)
    {
        if ($this->agent_id != $agent_id) {
            $this->has_changed = true;
        }
        $this->agent_id = $agent_id;
    }


    /**
     * @return DateTime
     */
    public function getStart()
    {
        return $this->start;
    }


    /**
     * @param DateTime $start
     */
    public function setStart(DateTime $start)
    {
        if ($this->start != $start) {
            $this->has_changed = true;
        }
        $this->start = $start;
    }


    /**
     * @return DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }


    /**
     * @param DateTime $end
     */
    public function setEnd(DateTime $end)
    {
        if ($this->end != $end) {
            $this->has_changed = true;
        }
        $this->end = $end;
    }


    /**
     * @return array
     */
    public function getInputs() : array
    {
        return $this->inputs;
    }


    /**
     * @param array $inputs
     */
    public function setInputs(array $inputs)
    {
        if ($this->inputs != $inputs) {
            $this->has_changed = true;
        }
        $this->inputs = $inputs;
    }


    /**
     * @return String
     */
    public function getRrule()
    {
        return $this->rrule;
    }


    /**
     * @param String $rrule
     */
    public function setRRule(String $rrule)
    {
        $this->rrule = $rrule;
    }


    /**
     * @return bool
     */
    public function hasChanged() : bool
    {
        return (bool) $this->has_changed;
    }
}