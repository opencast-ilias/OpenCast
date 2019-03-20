<?php
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
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctOpenCast $xoctOpenCast = null) {
		parent::__construct();
		if ($xoctOpenCast instanceof xoctOpenCast) {
			$this->xoctOpenCast = $xoctOpenCast;
		} else {
			$this->xoctOpenCast = new xoctOpenCast ();
		}
	}


	/**
	 *
	 */
	public function executeCommand() {
		if (!ilObjOpenCastAccess::hasWriteAccess()) {
			$this->ctrl->redirectByClass('xoctEventGUI');
		}
		$this->tabs->activateTab(ilObjOpenCastGUI::TAB_SETTINGS);
		$this->setSubTabs();
		parent::executeCommand();
	}


	/**
	 *
	 */
	protected function setSubTabs() {
		if (xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
			$this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
			$this->ctrl->setParameter($this, 'cmd', self::CMD_EDIT_GENERAL);
			$this->tabs->addSubTab(self::SUBTAB_GENERAL, $this->pl->txt('subtab_' . self::SUBTAB_GENERAL), $this->ctrl->getLinkTarget($this));
			$this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_WORKFLOW_PARAMETERS);
			$this->ctrl->setParameter($this, 'cmd', self::CMD_EDIT_WORKFLOW_PARAMS);
			$this->tabs->addSubTab(self::SUBTAB_WORKFLOW_PARAMETERS, $this->pl->txt('subtab_' . self::SUBTAB_WORKFLOW_PARAMETERS), $this->ctrl->getLinkTarget($this));
		}
	}

	/**
	 *
	 */
	protected function index() {
		$this->tabs->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
	}


	protected function edit() {
		$this->editGeneral();
	}

	/**
	 * @throws Exception
	 */
	protected function editGeneral() {
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo($this->pl->txt('series_has_duplicates'));
		}
		$this->tabs->activateSubTab(self::SUBTAB_GENERAL);

		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		$xoctSeriesFormGUI->fillForm();
		$this->tpl->setContent($xoctSeriesFormGUI->getHTML());
	}


	/**
	 * @throws xoctException
	 */
	protected function update() {
		$this->updateGeneral();
	}


	/**
	 * @throws xoctException
	 */
	protected function updateGeneral() {
		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		if ($xoctSeriesFormGUI->saveObject()) {
			$obj = new ilObjOpenCast($_GET['ref_id']);
			$obj->setTitle($this->xoctOpenCast->getSeries()->getTitle());
			$obj->setDescription($this->xoctOpenCast->getSeries()->getDescription());
			$obj->update();
			ilUtil::sendSuccess($this->pl->txt('series_saved'), true);
			$this->ctrl->redirect($this, self::CMD_EDIT_GENERAL);
		}
		$xoctSeriesFormGUI->setValuesByPost();
		$this->tpl->setContent($xoctSeriesFormGUI->getHTML());
	}


	protected function editWorkflowParameters() {
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo($this->pl->txt('series_has_duplicates'));
		}
		$this->tabs->activateSubTab(self::SUBTAB_WORKFLOW_PARAMETERS);

		$xoctSeriesFormGUI = new xoctSeriesWorkflowParametersFormGUI($this);
		$this->tpl->setContent($xoctSeriesFormGUI->getHTML());
	}

	protected function updateWorkflowParameters() {
		$xoctWorkflowParameterFormGUI = new xoctSeriesWorkflowParametersFormGUI($this);
		$xoctWorkflowParameterFormGUI->setValuesByPost();
		if ($xoctWorkflowParameterFormGUI->storeForm()) {
			ilUtil::sendSuccess($this->pl->txt('msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_EDIT_WORKFLOW_PARAMS);
		}
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function cancel() {
		$this->ctrl->redirectByClass('xoctEventGUI', xoctEventGUI::CMD_STANDARD);
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->xoctOpenCast->getObjId();
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