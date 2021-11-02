<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Cache\CacheFactory;
use srag\Plugins\Opencast\Model\API\Event\EventRepository;

/**
 * Class xoctEventAPI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventAPI {

	/**
	 * @var self
	 */
	protected static $instance;


	/**
	 * @return xoctEventAPI
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}


    /**
     * possible additional data:
     *
     *  description => text
     *  presenters => text
     *  workflow_parameters => array(text => int)
     *
     * @param String $series_id
     * @param String $title
     * @param        $start
     * @param        $end
     * @param String $location
     * @param array  $additional_data
     *
     * @return xoctEvent
     * @throws ilTimeZoneException
     */
	public function create($series_id, $title, $start, $end, $location, $additional_data = array()) {
		$event = new xoctEvent();
		$event->setSeriesIdentifier($series_id);
		$event->setTitle($title);
		$event->setStart($start instanceof DateTime ? $start : new DateTime($start));
		$event->setEnd($end instanceof DateTime ? $end : new DateTime($end));
		$event->setLocation($location);
		$event->setDescription(isset($additional_data['description']) ? $additional_data['description'] : '');
		$event->setPresenter(isset($additional_data['presenters']) ? $additional_data['presenters'] : '');
		$event->addDefaultWorkflowParameters();
		if (is_array($additional_data['workflow_parameters'])) {
            foreach ($additional_data['workflow_parameters'] as $param_id => $value) {
                $event->setWorkflowParameter($param_id, $value);
		    }
        }

		$std_acls = new xoctAclStandardSets();
		$event->setAcl($std_acls->getAcl());

		$event->schedule('', true);
		return $event;
	}

	/**
	 * @param $event_id
	 *
	 * @return xoctEvent
	 */
	public function read($event_id) {
		$event = new xoctEvent($event_id);
		return $event;
	}


	/**
	 * possible data:
	 *
	 *  title => text
	 *  start => date
	 *  end => date
	 *  location => text
	 *  description => text
	 *  presenters => text
	 *
	 * @param String $event_id
	 * @param array $data
	 *
	 * @return xoctEvent
	 */
	public function update($event_id, $data) {
		$event = new xoctEvent($event_id);

		// field 'online' is stored in ILIAS, not in Opencast
		if (isset($data['online'])) {
			$event->getXoctEventAdditions()->setIsOnline($data['online']);
			$event->getXoctEventAdditions()->update();
			unset($data['online']);
		}

		foreach ($data as $title => $value) {
			$setter = 'set'.$title;
			$event->$setter($value);
		}

		if (count($data)) { // this prevents an update, if only 'online' has changed
			$event->update();
		}

		return $event;
	}


    /**
     * @param $event_id
     *
     * @return bool
     * @throws xoctException
     */
	public function delete($event_id) {
		$event = new xoctEvent($event_id);
		$event->delete();
		return true;
	}


    /**
     * @param array $filter
     *
     * @return array
     * @throws xoctException
     */
    public function filter(array $filter){
        global $DIC;
        return (new EventRepository(CacheFactory::getInstance()))->getFiltered($filter);
    }

}