<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

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
	 *
	 * @param String $series_id
	 * @param String $title
	 * @param        $start
	 * @param        $end
	 * @param String $location
	 * @param array  $additional_data
	 *
	 * @return xoctEvent
	 *
	 */
	public function schedule($series_id, $title, $start, $end, $location, $additional_data = array()) {
		$event = new xoctEvent();
		$event->setSeriesIdentifier($series_id);
		$event->setTitle($title);
		$event->setStart($start instanceof DateTime ? $start : new DateTime($start));
		$event->setEnd($end instanceof DateTime ? $end : new DateTime($end));
		$event->setLocation($location);
		$event->setDescription(isset($additional_data['description']) ? $additional_data['description'] : '');
		$event->setPresenter(isset($additional_data['presenters']) ? $additional_data['presenters'] : '');

		$std_acls = new xoctAclStandardSets();
		$event->setAcl($std_acls->getAcls());

		$event->schedule();
		return $event;
	}


	/**
	 * @param $event_id
	 * @param $online
	 *
	 * @return xoctEvent
	 */
	public function setOnline($event_id, $online) {
		$event = new xoctEvent($event_id);
		$event->getXoctEventAdditions()->setIsOnline($online);
		$event->getXoctEventAdditions()->update();
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
		foreach ($data as $title => $value) {
			$setter = 'set'.$title;
			$event->$setter($value);
		}
		$event->update();
		return $event;
	}


	/**
	 * @param $event_id
	 *
	 * @return bool
	 */
	public function delete($event_id) {
		$event = new xoctEvent($event_id);
		$event->delete();
		return true;
	}



}