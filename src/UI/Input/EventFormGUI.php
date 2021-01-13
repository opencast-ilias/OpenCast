<?php

namespace srag\Plugins\Opencast\UI\Input;

use ilDateTime;
use ilDateTimeException;
use ilDateTimeInputGUI;
use ilException;
use ilInteractiveVideoTimePicker;
use ilObjOpenCast;
use ilObjOpenCastAccess;
use ilObjPluginDispatchGUI;
use ilOpenCastPlugin;
use ilPropertyFormGUI;
use ilRadioGroupInputGUI;
use ilRadioOption;
use ilSelectInputGUI;
use ilTextAreaInputGUI;
use ilTextInputGUI;
use ilTimeZone;
use ilTimeZoneException;
use ilUtil;
use ReflectionException;
use srag\DIC\OpenCast\DICTrait;
use srag\CustomInputGUIs\OpenCast\WeekdayInputGUI\WeekdayInputGUI;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\API\Agent\Agent;
use srag\Plugins\Opencast\Model\API\Event\EventRepository;
use srag\Plugins\Opencast\Model\API\Series\SeriesRepository;
use xoct;
use xoctConf;
use xoctEvent;
use xoctEventGUI;
use xoctException;
use xoctOpenCast;
use xoctSeries;
use xoctSeriesWorkflowParameterRepository;
use xoctUploadFile;
use xoctUser;

/**
 * Class xoctEventFormGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class EventFormGUI extends ilPropertyFormGUI {


	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const IDENTIFIER = xoctEventGUI::IDENTIFIER;

    const PARENT_CMD_UPLOAD_CHUNKS = 'uploadChunks';
    const PARENT_CMD_CREATE = 'create';
    const PARENT_CMD_CANCEL = 'cancel';
    const PARENT_CMD_UPDATE = 'update';

    const PARENT_CMD_CREATE_SCHEDULED = 'createScheduled';
    const F_TITLE = 'title';
    const F_DESCRIPTION = 'description';
    const F_FILE_PRESENTER = 'file_presenter';
    const F_FILE_PRESENTATION = 'file_presenter';
    const F_IDENTIFIER = 'identifier';
    const F_CREATOR = 'creator';
    const F_DURATION = 'duration';
    const F_PROCESSING_STATE = 'processing_state';
    const F_START_TIME = 'start_time';
    const F_PRESENTERS = 'presenters';
    const F_START = 'start';
    const F_END = 'end';
    const F_LOCATION = 'location';
    const F_SOURCE = 'source';
    const F_WORKFLOW_PARAMETER = 'workflow_parameter';
    const F_ONLINE = 'online';
    const F_MULTIPLE = 'multiple';
    const F_MULTIPLE_START = 'multiple_start';
    const F_MULTIPLE_START_TIME = 'multiple_start_time';
    const F_MULTIPLE_END = 'multiple_end';
    const F_MULTIPLE_END_TIME = 'multiple_end_time';
    const F_MULTIPLE_WEEKDAYS = 'multiple_weekdays';

    const F_SERIES = 'series';
    const OPT_OWN_SERIES = 'own_series';
    /**
	 * @var  xoctEvent
	 */
	protected $object;
	/**
	 * @var
	 */
	protected $parent_gui;
	/**
	 * @var bool
	 */
	protected $external = true;
	/**
	 * @var bool
	 */
	protected $schedule;
    /**
     * @var bool
     */
    protected $is_new;
    /**
     * @var xoctOpenCast
     */
    protected $xoctOpenCast;
    /**
     * @var string|null
     */
    protected $cmd_url_upload_chunks;
    /**
     * @var null
     */
    protected $form_action;
    /**
     * @var EventRepository
     */
    protected $event_repository;
    /**
     * @var SeriesRepository
     */
    protected $series_repository;


    /**
     * @param              $parent_gui
     * @param xoctEvent    $object
     * @param xoctOpenCast $xoctOpenCast
     * @param bool         $schedule
     *
     * @param null         $form_action
     * @param null         $cmd_url_upload_chunks
     *
     * @throws DICException
     * @throws ilDateTimeException
     * @throws xoctException
     */
	public function __construct(
	    $parent_gui,
        xoctEvent $object,
        xoctOpenCast $xoctOpenCast = null,
        $schedule = false,
        $form_action = null,
        $cmd_url_upload_chunks = null
    ) {
		parent::__construct();
		$this->event_repository = new EventRepository(self::dic()->dic());
		$this->series_repository = new SeriesRepository();
		$this->cmd_url_upload_chunks = $cmd_url_upload_chunks ?? self::dic()->ctrl()->getLinkTarget($parent_gui, self::PARENT_CMD_UPLOAD_CHUNKS);
        self::dic()->ctrl()->saveParameter($parent_gui, self::IDENTIFIER);
        $this->form_action = $form_action ?? self::dic()->ctrl()->getFormAction($parent_gui);
        $this->object = $object;
        $this->xoctOpenCast = $xoctOpenCast;
        $this->parent_gui = $parent_gui;
        $this->is_new = ($this->object->getIdentifier() == '');
        $this->schedule = $schedule;
        self::dic()->language()->loadLanguageModule('form');
        $this->setId('xoct_event');

        $this->initForm();
    }


	/**
	 *
	 */
	public function setValuesByPost() {
		/**
		 * @var $item ilTextInputGUI
		 */
		foreach ($this->getItems() as $item) {
			if ($item->getPostVar() != self::F_START) {
				$item->setValueByArray($_POST);
			}
		}
	}


    /**
     * @throws DICException
     * @throws ilDateTimeException
     * @throws xoctException
     */
	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->form_action);
		$this->initButtons();

		if (is_null($this->xoctOpenCast)) {
		    $series_input = new ilSelectInputGUI($this->txt(self::F_SERIES), self::F_SERIES);
		    $series_input->setOptions($this->getSeriesOptions());
		    $this->addItem($series_input);
        }

		$te = new ilTextInputGUI($this->txt(self::F_TITLE), self::F_TITLE);
		$te->setRequired(!$this->is_new || $this->schedule);
		$this->addItem($te);

		if ($this->is_new && !$this->schedule) {
			$allow_audio = xoctConf::getConfig(xoctConf::F_AUDIO_ALLOWED);

			$te = new FileUploadInputGUI($this, self::PARENT_CMD_CREATE, $this->txt(self::F_FILE_PRESENTER . ($allow_audio ? '_w_audio' : '')), self::F_FILE_PRESENTER);
			$te->setUrl($this->cmd_url_upload_chunks);
			$te->setSuffixes($allow_audio ? array(
				'mov',
				'mp4',
				'm4v',
				'flv',
				'mpeg',
				'avi',
				'mp4',
				'mp3',
				'm4a',
				'wma',
				'aac',
				'ogg',
				'flac',
				'aiff',
				'wav'
			) : array(
				'mov',
				'mp4',
				'm4v',
				'flv',
				'mpeg',
				'avi',
				'mp4',
			));
			$te->setMimeTypes($allow_audio ? array(
				'video/avi',
				'video/quicktime',
				'video/mpeg',
				'video/mp4',
				'video/ogg',
				'video/webm',
				'video/x-ms-wmv',
				'video/x-flv',
				'video/x-matroska',
				'video/x-msvideo',
				'video/x-dv',
                'video/x-m4v',
				'audio/mp4',
				'audio/x-m4a',
				'audio/ogg',
				'audio/mpeg',
				'audio/mp3',
				'audio/x-aiff',
				'audio/aiff',
				'audio/x-wav',
				'audio/wav',
				'audio/aac',
				'audio/flac',
				'audio/x-ms-wma',
				'audio/basic'
			) : array(
				'video/avi',
				'video/quicktime',
				'video/mpeg',
				'video/mp4',
				'video/ogg',
				'video/webm',
				'video/x-ms-wmv',
				'video/x-flv',
				'video/x-matroska',
				'video/x-msvideo',
				'video/x-dv',
                'video/x-m4v'
			));
			$te->setRequired(true);
			$this->addItem($te);
		}

		$te = new ilTextAreaInputGUI($this->txt(self::F_DESCRIPTION), self::F_DESCRIPTION);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->txt(self::F_PRESENTERS), self::F_PRESENTERS);
		$te->setRequired($this->schedule);
		$this->addItem($te);


		// show location and start date for scheduled events only if configured
        $date_and_location_disabled = $this->object->isScheduled() && xoctConf::getConfig(xoctConf::F_SCHEDULED_METADATA_EDITABLE) == xoctConf::METADATA_EXCEPT_DATE_PLACE;

		if (xoct::isApiVersionGreaterThan('v1.1.0') && ($this->schedule || $this->object->isScheduled())) {
			$input = new ilSelectInputGUI($this->txt(self::F_LOCATION), self::F_LOCATION);
			$options = array();
			/** @var Agent $agent */
			foreach (Agent::getAllAgents() as $agent) {
				$options[$agent->getAgentId()] = $agent->getAgentId();
			}
			$input->setOptions($options);
		} else {
			$input = new ilTextInputGUI($this->txt(self::F_LOCATION), self::F_LOCATION);
		}
		$input->setDisabled($date_and_location_disabled);
		$this->addItem($input);

		if (!$this->schedule) {
			$date = new ilDateTimeInputGUI($this->txt(self::F_START), self::F_START);
			$date->setShowTime(true);
			$date->setShowSeconds(false);
			$date->setMinuteStepSize(1);
			$date->setDisabled($date_and_location_disabled);
			$date->setRequired(true);
			$this->addItem($date);
		}

		if ($this->object->isScheduled() && !$this->schedule) {
			$date = new ilDateTimeInputGUI($this->txt(self::F_END), self::F_END);
			$date->setShowTime(true);
			$date->setShowSeconds(false);
			$date->setMinuteStepSize(1);
			$date->setDisabled($date_and_location_disabled);
            $date->setRequired(true);
            $this->addItem($date);
		}

		if ($this->schedule) {
			$radio = new ilRadioGroupInputGUI($this->txt(self::F_MULTIPLE), self::F_MULTIPLE);

			// SINGLE EVENT
			$opt = new ilRadioOption(self::dic()->language()->txt('no'), 0);

			$date = new ilDateTimeInputGUI($this->txt(self::F_START), self::F_START);
			$date->setShowTime(true);
			$date->setShowSeconds(false);
			$date->setMinuteStepSize(1);
            $date->setDate(new ilDateTime(time(), IL_CAL_UNIX));
            $date->setRequired(true);
            $opt->addSubItem($date);

			$date = new ilDateTimeInputGUI($this->txt(self::F_END), self::F_END);
			$date->setShowTime(true);
			$date->setShowSeconds(false);
			$date->setMinuteStepSize(1);
			$date->setDate(new ilDateTime(time(), IL_CAL_UNIX));
            $date->setRequired(true);
			$opt->addSubItem($date);

			$radio->addOption($opt);

			// MULTIPLE EVENTS
			$opt = new ilRadioOption(self::dic()->language()->txt('yes'), 1);

			$subinput = new ilDateTimeInputGUI($this->txt(self::F_MULTIPLE_START), self::F_MULTIPLE_START);
			$subinput->setRequired(true);
			$opt->addSubItem($subinput);

			$subinput = new ilDateTimeInputGUI($this->txt(self::F_MULTIPLE_END), self::F_MULTIPLE_END);
			$subinput->setRequired(true);
			$opt->addSubItem($subinput);

			$subinput = new WeekdayInputGUI($this->txt(self::F_MULTIPLE_WEEKDAYS), self::F_MULTIPLE_WEEKDAYS);
			$subinput->setRequired(true);
			$opt->addSubItem($subinput);

			$subinput = new ilInteractiveVideoTimePicker($this->txt(self::F_MULTIPLE_START_TIME), self::F_MULTIPLE_START_TIME);
			$subinput->setRequired(true);
			$opt->addSubItem($subinput);

			$subinput = new ilInteractiveVideoTimePicker($this->txt(self::F_MULTIPLE_END_TIME), self::F_MULTIPLE_END_TIME);
			$subinput->setRequired(true);
			$opt->addSubItem($subinput);

			$radio->addOption($opt);

			$this->addItem($radio);
		}

		// if ($this->is_new || $this->object->isScheduled()) {
		if ($this->is_new) {
		    $form_items = is_null($this->xoctOpenCast) ?
                xoctSeriesWorkflowParameterRepository::getInstance()->getGeneralFormItems() :
                xoctSeriesWorkflowParameterRepository::getInstance()->getFormItemsForObjId(
                    $this->xoctOpenCast->getObjId(),
                    ilObjOpenCastAccess::hasPermission('edit_videos')
                );
			foreach ($form_items as $item) {
				$this->addItem($item);
			}
		}
	}


	/**
	 *
	 */
	public function fillForm() {
		$startDateTime = $this->object->getStart();
		$endDateTime = $this->object->getEnd();
		$start = $startDateTime->format('Y-m-d H:i:s');
		$end = $endDateTime ? $endDateTime->format('Y-m-d H:i:s') : '';

		$array = array(
			self::F_TITLE            => $this->object->getTitle(),
			self::F_DESCRIPTION      => $this->object->getDescription(),
			self::F_IDENTIFIER       => $this->object->getIdentifier(),
			self::F_CREATOR          => $this->object->getCreator(),
			self::F_DURATION         => $this->object->getDurationArrayForInput(),
			self::F_PROCESSING_STATE => $this->object->getProcessingState(),
			self::F_PRESENTERS       => $this->object->getPresenter(),
			self::F_LOCATION         => $this->object->getLocation(),
			self::F_SOURCE           => $this->object->getSource(),
			self::F_START          => $start,
			self::F_END          => $end,
		);

		$this->setValuesByArray($array, true);
	}

    /**
     * @return bool
     * @throws DICException
     * @throws ilTimeZoneException
     * @throws xoctException
     */
	public function fillObject() : bool
    {
	    $check_input = $this->checkInput();
	    $check_date = $this->checkDates();
		if (!$check_input || !$check_date) {
			return false;
		}

        if (is_null($this->xoctOpenCast)) {
            $series_id = $this->getInput(self::F_SERIES);
            if ($series_id == self::OPT_OWN_SERIES) {
                $series_id = (new SeriesRepository())->getOrCreateOwnSeries(xoctUser::getInstance(self::dic()->user()))->getIdentifier();
            }
        } else {
            $series_id = $this->xoctOpenCast->getSeriesIdentifier();
        }
        $this->object->setSeriesIdentifier($series_id);

        $presenter = xoctUploadFile::getInstanceFromFileArray('file_presenter');
        $title = $this->getInput(self::F_TITLE);
        $this->object->setTitle($title != '' ? $title : $presenter->getTitle());
		$this->object->setDescription($this->getInput(self::F_DESCRIPTION));
		$this->object->setLocation($this->getInput(self::F_LOCATION));
		$this->object->setPresenter($this->getInput(self::F_PRESENTERS));
		is_null($this->xoctOpenCast) ?
            $this->object->setGeneralWorkflowParameters((array) $this->getInput(self::F_WORKFLOW_PARAMETER)) :
            $this->object->setWorkflowParametersForObjId((array) $this->getInput(self::F_WORKFLOW_PARAMETER), $this->xoctOpenCast->getObjId(), ilObjOpenCastAccess::hasPermission('edit_videos'));

        $date_and_location_disabled = $this->object->isScheduled() && xoctConf::getConfig(xoctConf::F_SCHEDULED_METADATA_EDITABLE) == xoctConf::METADATA_EXCEPT_DATE_PLACE;

        if ($this->getInput(self::F_MULTIPLE)) {
			$start_date = $this->getInput(self::F_MULTIPLE_START);
			$start_time = $this->getInput(self::F_MULTIPLE_START_TIME);
			$start = $start_date . ' ' . floor($start_time/3600) . ':' . floor($start_time/60%60) . ':' . $start_time%60;
			$this->object->setStart($start);

			// the start date is used for end date, since the enddate defines the end of the recurrence, not of the actual event
            $end_date = $this->getInput(self::F_MULTIPLE_END);
            $end_time = $this->getInput(self::F_MULTIPLE_END_TIME);
			$end = $end_date . ' ' . floor($end_time/3600) . ':' . floor($end_time/60%60) . ':' . ($end_time%60);
			$this->object->setEnd($end);

			$duration = ($end_time - $start_time) * 1000;
			$this->object->setDuration($duration);
		} else if (!$date_and_location_disabled) {
            /**
			 * @var $start            ilDateTime
			 * @var $ilDateTimeInputGUI ilDateTimeInputGUI
			 */
			$ilDateTimeInputGUI = $this->getItemByPostVar(self::F_START);
			$start = $ilDateTimeInputGUI->getDate();
			$default_datetime = $this->object->getDefaultDateTimeObject($start->get(IL_CAL_ISO_8601, '', ilTimeZone::_getInstance()->getIdentifier()));
			$this->object->setStart($default_datetime);

			if ($this->object->isScheduled() || $this->schedule) {
				/**
				 * @var $start            ilDateTime
				 * @var $ilDateTimeInputGUI ilDateTimeInputGUI
				 */
				$ilDateTimeInputGUI = $this->getItemByPostVar(self::F_END);
				$end = $ilDateTimeInputGUI->getDate();
				$default_datetime = $this->object->getDefaultDateTimeObject($end->get(IL_CAL_ISO_8601, '', ilTimeZone::_getInstance()->getIdentifier()));
				$this->object->setEnd($default_datetime);
			}
		}

		return true;
	}


    /**
     * @return bool
     * @throws DICException
     */
    protected function checkDates() : bool
    {
        $date_and_location_disabled = xoctConf::getConfig(xoctConf::F_SCHEDULED_METADATA_EDITABLE) == xoctConf::METADATA_EXCEPT_DATE_PLACE;
        if (($this->object->isScheduled() && !$date_and_location_disabled) || $this->schedule) {
            if ($this->getInput(self::F_MULTIPLE)) {
                $start_date = $this->getInput(self::F_MULTIPLE_START);
                $start_time = $this->getInput(self::F_MULTIPLE_START_TIME);
                $start = $start_date . ' ' . floor($start_time/3600) . ':' . floor($start_time/60%60) . ':' . $start_time%60;

                $end_date = $this->getInput(self::F_MULTIPLE_END);
                $end_time = $this->getInput(self::F_MULTIPLE_END_TIME);
                $end = $end_date . ' ' . floor($end_time/3600) . ':' . floor($end_time/60%60) . ':' . ($end_time%60);
            } else {
                $start = $this->getInput(self::F_START);
                $end = $this->getInput(self::F_END);
            }

            if ($end && ($end < $start)) {
                ilUtil::sendFailure(self::plugin()->translate('event_msg_end_before_start'), true);
                return false;
            }

            $now = date('Y-m-d H:i:s');
            if (($start && ($start < $now)) || ($end && ($end < $now))) {
                ilUtil::sendFailure(self::plugin()->translate('event_msg_scheduled_in_past'), true);
                return false;
            }
        }

        return true;
	}


    /**
     * @param $key
     *
     * @return string
     */
	protected function txt($key) : string
    {
		return $this->parent_gui->txt($key);
	}


    /**
     * @param $key
     *
     * @return string
     * @throws DICException
     */
	protected function infoTxt($key) : string
    {
		return self::plugin()->translate($key . '_info', 'event');
	}


    /**
     * @throws DICException
     * @throws ReflectionException
     * @throws ilException
     * @throws ilTimeZoneException
     * @throws xoctException
     */
	public function saveObject() : bool
    {
		if (!$this->fillObject()) {
			return false;
		}
		if ($this->object->getIdentifier()) {
			try {
				$this->object->update();
			} catch (xoctException $e) {
				return $this->checkAndShowConflictMessage($e);
			}
			$this->object->getXoctEventAdditions()->update();
		} else {
            if ($this->schedule) {
                try {
                    $this->object->schedule($this->buildRRule());
                } catch (xoctException $e) {
                    return $this->checkAndShowConflictMessage($e);
                }
            } else {
                $this->event_repository->upload($this->object);
			}
		}

		return true;
	}

	/**
	 * @return bool|string
	 */
	protected function buildRRule()
    {
		if ($this->getInput(self::F_MULTIPLE)) {
			$start_time = $this->getInput(self::F_MULTIPLE_START_TIME);
			$byhour = floor($start_time / 3600);
			$byminute = floor($start_time / 60) % 60;

			$weekdays = $this->getInput(self::F_MULTIPLE_WEEKDAYS);
			$byday = implode(',', $weekdays);
			$rrule = "FREQ=WEEKLY;BYDAY=$byday;BYHOUR=$byhour;BYMINUTE=$byminute;";
			return $rrule;
		}
		return false;
	}

	/**
	 *
	 */
	protected function initButtons()
    {
		switch (true) {
			case  $this->is_new AND !$this->view AND !$this->schedule:
				$this->setTitle($this->txt('create'));
				$this->addCommandButton(self::PARENT_CMD_CREATE, $this->txt(self::PARENT_CMD_CREATE));
				$this->addCommandButton(self::PARENT_CMD_CANCEL, $this->txt(self::PARENT_CMD_CANCEL));
				break;
			case $this->is_new AND $this->schedule:
				$this->setTitle($this->txt('schedule_new'));
				$this->addCommandButton(self::PARENT_CMD_CREATE_SCHEDULED, $this->txt(self::PARENT_CMD_CREATE_SCHEDULED));
				$this->addCommandButton(self::PARENT_CMD_CANCEL, $this->txt(self::PARENT_CMD_CANCEL));
				break;
			case  !$this->is_new:
				if (ilObjOpenCast::DEV) {
					$this->addCommandButton('saveAndStay', 'Save and Stay');
				}
				$this->setTitle($this->txt('edit'));
				$this->addCommandButton(self::PARENT_CMD_UPDATE, $this->txt(self::PARENT_CMD_UPDATE));
				$this->addCommandButton(self::PARENT_CMD_CANCEL, $this->txt(self::PARENT_CMD_CANCEL));
				break;
		}
	}


	/**
	 * @return xoctEvent
	 */
	public function getObject() : xoctEvent
    {
		return $this->object;
	}


	/**
	 * @param xoctEvent $object
	 */
	public function setObject(xoctEvent $object)
    {
		$this->object = $object;
	}

    /**
     * @param xoctException $e
     * @return bool
     * @throws xoctException
     */
	protected function checkAndShowConflictMessage(xoctException $e) : bool
    {
		if ($e->getCode() == xoctException::API_CALL_STATUS_409) {
			$conflicts = json_decode(substr($e->getMessage(), 10), true);
			$message = $this->txt('msg_scheduling_conflict') . '<br>';
			foreach ($conflicts as $conflict) {
				$message .= '<br>' . $conflict['title'] . '<br>' . date('Y.m.d H:i:s', strtotime($conflict['start'])) . ' - '
					. date('Y.m.d H:i:s', strtotime($conflict['end'])) . '<br>';
			}
			ilUtil::sendFailure($message);

			return false;
		}
		throw $e;
	}


    /**
     * @return array
     * @throws xoctException
     */
    protected function getSeriesOptions() : array
    {
        $xoct_user = xoctUser::getInstance(self::dic()->user());
        // fetch early, because acls will be refreshed
        $own_series = $this->series_repository->getOwnSeries($xoct_user);
        $series_options = [];
        foreach (xoctSeries::getAllForUser($xoct_user->getUserRoleName()) as $series) {
            $series_options[$series->getIdentifier()] = $series->getTitle() . ' (...' . substr($series->getIdentifier(), -4, 4) . ')';
        }

        natcasesort($series_options);
        if (is_null($own_series)) {
            $series_options =
                [self::OPT_OWN_SERIES => $this->series_repository->getOwnSeriesTitle($xoct_user)]
                + $series_options;
        }

        return $series_options;
    }
}


