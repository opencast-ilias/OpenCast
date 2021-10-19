<?php

use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;

/**
 * Class xoctSeriesGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctSeriesGUI : ilObjOpenCastGUI
 */
class xoctSeriesGUI extends xoctGUI {

	const SERIES_ID = 'series_id';

	const CMD_EDIT_GENERAL = 'editGeneral';
	const CMD_EDIT = self::CMD_EDIT_GENERAL;
	const CMD_EDIT_WORKFLOW_PARAMS = 'editWorkflowParameters';
	const CMD_UPDATE_GENERAL = 'updateGeneral';
	const CMD_UPDATE = self::CMD_UPDATE_GENERAL;
	const CMD_UPDATE_WORKFLOW_PARAMS = 'updateWorkflowParameters';

	const SUBTAB_GENERAL = 'general';
	const SUBTAB_WORKFLOW_PARAMETERS = 'workflow_params';

	/**
	 * @var xoctOpenCast
	 */
	protected $xoctOpenCast;
    /**
     * @var ilObjOpenCast
     */
	protected $object;

    /**
     * @param ilObjOpenCast $object
     * @param xoctOpenCast  $xoctOpenCast
     */
	public function __construct(ilObjOpenCast $object, xoctOpenCast $xoctOpenCast = null) {
		if ($xoctOpenCast instanceof xoctOpenCast) {
			$this->xoctOpenCast = $xoctOpenCast;
		} else {
			$this->xoctOpenCast = new xoctOpenCast ();
		}
        $this->object = $object;
    }


	/**
	 *
	 */
	public function executeCommand() {
		if (!ilObjOpenCastAccess::hasWriteAccess()) {
			self::dic()->ctrl()->redirectByClass('xoctEventGUI');
		}
		self::dic()->tabs()->activateTab(ilObjOpenCastGUI::TAB_SETTINGS);
		$this->setSubTabs();
		parent::executeCommand();
	}


	/**
	 *
	 */
	protected function setSubTabs() {
		if (xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
			self::dic()->ctrl()->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
			self::dic()->ctrl()->setParameter($this, 'cmd', self::CMD_EDIT_GENERAL);
			self::dic()->tabs()->addSubTab(self::SUBTAB_GENERAL, self::plugin()->translate('subtab_' . self::SUBTAB_GENERAL), self::dic()->ctrl()->getLinkTarget($this));
			self::dic()->ctrl()->setParameter($this, 'subtab_active', self::SUBTAB_WORKFLOW_PARAMETERS);
			self::dic()->ctrl()->setParameter($this, 'cmd', self::CMD_EDIT_WORKFLOW_PARAMS);
			self::dic()->tabs()->addSubTab(self::SUBTAB_WORKFLOW_PARAMETERS, self::plugin()->translate('subtab_' . self::SUBTAB_WORKFLOW_PARAMETERS), self::dic()->ctrl()->getLinkTarget($this));
		}
	}

	/**
	 *
	 */
	protected function index() {
		self::dic()->tabs()->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
	}


	/**
	 * @throws Exception
	 */
	protected function edit() {
		$this->editGeneral();
	}

	/**
	 * @throws Exception
	 */
	protected function editGeneral() {
        $this->object->updateObjectFromSeries();
        if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates'));
		}
		self::dic()->tabs()->activateSubTab(self::SUBTAB_GENERAL);

		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		$xoctSeriesFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctSeriesFormGUI->getHTML());
	}


	/**
	 * @throws xoctException
	 */
	protected function update() {
		$this->updateGeneral();
	}

    /**
     * @throws DICException
     * @throws arException
     * @throws ilException
     * @throws xoctException
     */
	protected function updateGeneral() {
		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		if ($xoctSeriesFormGUI->saveObject()) {
			$this->getObject()->setTitle($this->xoctOpenCast->getSeries()->getTitle());
            $this->getObject()->setDescription($this->xoctOpenCast->getSeries()->getDescription());
            $this->getObject()->update();
            $this->xoctOpenCast->updateAllDuplicates();
			ilUtil::sendSuccess(self::plugin()->translate('series_saved'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_EDIT_GENERAL);
		}
		$xoctSeriesFormGUI->setValuesByPost();
		self::dic()->mainTemplate()->setContent($xoctSeriesFormGUI->getHTML());
	}


	/**
	 * @throws DICException
	 */
	protected function editWorkflowParameters() {
		SeriesWorkflowParameterRepository::getInstance()->syncAvailableParameters($this->getObjId());
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates'));
		}
		self::dic()->tabs()->activateSubTab(self::SUBTAB_WORKFLOW_PARAMETERS);

		$xoctSeriesFormGUI = new xoctSeriesWorkflowParameterTableGUI($this, self::CMD_EDIT_WORKFLOW_PARAMS);
		self::dic()->mainTemplate()->setContent($xoctSeriesFormGUI->getHTML());
	}


	/**
	 * @throws DICException
	 */
	protected function updateWorkflowParameters() {
		foreach (filter_input(INPUT_POST, 'workflow_parameter', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) as $param_id => $value) {
			$value_admin = $value['value_admin'];
			$value_member = $value['value_member'];
			if (in_array($value_member, WorkflowParameter::$possible_values) && in_array($value_admin, WorkflowParameter::$possible_values)) {
				SeriesWorkflowParameterRepository::getByObjAndParamId($this->getObjId(), $param_id)->setValueAdmin($value_admin)->setValueMember($value_member)->update();
			}
		}
		ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
		self::dic()->ctrl()->redirect($this, self::CMD_EDIT_WORKFLOW_PARAMS);
	}

	/**
	 *
	 */
	protected function cancel() {
		self::dic()->ctrl()->redirectByClass('xoctEventGUI', xoctEventGUI::CMD_STANDARD);
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->xoctOpenCast->getObjId();
	}

	public function getObject() : ilObjOpenCast
    {
        return $this->object;
    }
	/**
	 *
	 */
	protected function add() {
	}


	/**
	 *
	 */
	protected function create() {
	}


	/**
	 *
	 */
	protected function confirmDelete() {
	}


	/**
	 *
	 */
	protected function delete() {
	}


	/**
	 *
	 */
	protected function view() {
	}
}
