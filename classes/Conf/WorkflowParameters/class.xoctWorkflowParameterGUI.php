<?php

use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;

/**
 * Class xoctWorkflowParameterGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowParameterGUI : xoctMainGUI
 */
class xoctWorkflowParameterGUI extends xoctGUI {

	const SUBTAB_PARAMETERS = 'parameters';
	const SUBTAB_SETTINGS = 'settings';

	const CMD_SHOW_TABLE = 'showTable';
	const CMD_SHOW_FORM = 'showForm';
	const CMD_UPDATE_FORM = 'updateForm';
	const CMD_UPDATE_PARAMETER = 'updateParameter';
	const CMD_UPDATE_TABLE = 'updateTable';
	const CMD_LOAD_WORKFLOW_PARAMS = 'loadWorkflowParameters';
	const CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED = 'loadWorkflowParametersConfirmed';
	const CMD_OVERWRITE_SERIES_PARAMETERS = 'overwriteSeriesParameters';
	/**
	 * @var bool
	 */
	protected $overwrite_series_parameters = false;

	/**
	 *
	 */
	protected function index() {
		$this->showTable();
	}


	/**
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 */
	protected function setSubTabs() {
		self::dic()->tabs()->addSubTab(self::SUBTAB_PARAMETERS, self::plugin()->translate(self::SUBTAB_PARAMETERS, 'subtab'), self::dic()->ctrl()->getLinkTarget($this, self::CMD_STANDARD));
		self::dic()->tabs()->addSubTab(self::SUBTAB_SETTINGS, self::plugin()->translate(self::SUBTAB_SETTINGS, 'subtab'), self::dic()->ctrl()->getLinkTarget($this, self::CMD_SHOW_FORM));
	}


	/**
	 *
	 */
	protected function showTable() {
		ilUtil::sendInfo(self::plugin()->translate('msg_workflow_parameters_info'));
		self::dic()->tabs()->setSubTabActive(self::SUBTAB_PARAMETERS);
		$xoctWorkflowParameterTableGUI = new xoctWorkflowParameterTableGUI($this, self::CMD_SHOW_TABLE);
		self::dic()->ui()->mainTemplate()->setContent($xoctWorkflowParameterTableGUI->getHTML());
	}


	/**
	 *
	 */
	protected function showForm() {
		self::dic()->tabs()->setSubTabActive(self::SUBTAB_SETTINGS);
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
		self::dic()->ui()->mainTemplate()->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}

	/**
	 * @param $cmd
	 */
	protected function performCommand($cmd) {
		$this->initToolbar($cmd);
		$this->setSubTabs();
		$this->{$cmd}();
	}

	/**
	 *
	 */
	protected function initToolbar($cmd) {
		switch ($cmd) {
			case self::CMD_STANDARD:
			case self::CMD_SHOW_TABLE:
				$button = ilLinkButton::getInstance();
				$button->setCaption(self::plugin()->translate('config_btn_load_parameters'), false);
				$button->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_LOAD_WORKFLOW_PARAMS));
				self::dic()->toolbar()->addButtonInstance($button);
				$button = ilLinkButton::getInstance();
				$button->setCaption(self::plugin()->translate('config_btn_add_parameter'), false);
				$button->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD));
				$button->setPrimary(true);
				self::dic()->toolbar()->addButtonInstance($button);
				break;
			default:
				break;
		}
	}


	/**
	 *
	 */
	protected function loadWorkflowParameters() {
		try {
			$params = WorkflowParameterRepository::getInstance()->loadParametersFromAPI();
			if (!count($params)) {
				ilUtil::sendFailure(self::plugin()->translate('msg_no_params_found'), true);
				self::dic()->ctrl()->redirect($this, self::CMD_SHOW_TABLE);
			}
			$ilConfirmationGUI = new ilConfirmationGUI();
			$ilConfirmationGUI->setFormAction(self::dic()->ctrl()->getFormAction($this));
			$ilConfirmationGUI->setCancel(self::dic()->language()->txt('cancel'), self::CMD_SHOW_TABLE);
			$ilConfirmationGUI->setConfirm(self::dic()->language()->txt('confirm'), self::CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED);
			$ilConfirmationGUI->setHeaderText(self::plugin()->translate('msg_load_workflow_params'));
			/** @var WorkflowParameter $param */
			foreach ($params as $param) {
				$ilConfirmationGUI->addItem('workflow_params[' . $param->getId() . '][title]', $param->getTitle(), $param->getTitle());
				$ilConfirmationGUI->addHiddenItem('workflow_params[' . $param->getId() . '][type]', $param->getType());
			}
			self::dic()->ui()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
		} catch (xoctException $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			self::dic()->ctrl()->redirect($this, self::CMD_SHOW_TABLE);
		}
	}


	/**
	 *
	 */
	protected function loadWorkflowParametersConfirmed() {
		$existing_ids = WorkflowParameter::getArray(null, 'id');
		$delivered_params = filter_input(INPUT_POST, 'workflow_params', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		$delivered_ids = array_keys($delivered_params);
		$to_delete_ids = array_diff($existing_ids, $delivered_ids);
		$to_create_ids = array_diff($delivered_ids, $existing_ids);
		$to_create = [];

		// create new and update existing
		foreach ($delivered_params as $param_id => $parameter) {
			$xoctWorkflowParameter = WorkflowParameterRepository::getInstance()->createOrUpdate($param_id, $parameter['title'], $parameter['type']);
			if (in_array($param_id, $to_create_ids)) {
				$to_create[] = $xoctWorkflowParameter;
			}
		}

		// delete not delivered
		foreach ($to_delete_ids as $id_to_delete) {
			WorkflowParameter::find($id_to_delete)->delete();
		}

		// create/delete the series settings
		if (count($to_delete_ids)) {
			SeriesWorkflowParameterRepository::getInstance()->deleteParamsForAllObjectsById($to_delete_ids);
		}
		if (count($to_create_ids)) {
			SeriesWorkflowParameterRepository::getInstance()->createParamsForAllObjects($to_create);
		}

		ilUtil::sendSuccess(self::plugin()->translate('config_msg_success'), true);
		self::dic()->ctrl()->redirect($this, self::CMD_SHOW_TABLE);
	}


	/**
	 *
	 */
	protected function edit() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this, filter_input(INPUT_GET, 'param_id'));
		self::dic()->ui()->mainTemplate()->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function add() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this);
		self::dic()->ui()->mainTemplate()->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function create() {
		$this->updateParameter();
	}


	/**
	 *
	 */
	protected function update() {

	}


	/**
	 *
	 */
	protected function updateForm() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
		$xoctWorkflowParameterFormGUI->setValuesByPost();
		if ($xoctWorkflowParameterFormGUI->storeForm()) {
			ilUtil::sendSuccess(self::plugin()->translate('config_msg_success'), true);
			if ($this->overwrite_series_parameters) {
				$ilConfirmationGUI = new ilConfirmationGUI();
				$ilConfirmationGUI->setFormAction(self::dic()->ctrl()->getFormAction($this));
				$ilConfirmationGUI->setCancel(self::dic()->language()->txt('cancel'), self::CMD_STANDARD);
				$ilConfirmationGUI->setConfirm(self::dic()->language()->txt('confirm'), self::CMD_OVERWRITE_SERIES_PARAMETERS);
				$ilConfirmationGUI->setHeaderText(self::plugin()->translate('msg_confirm_overwrite_series_params'));
				self::dic()->ui()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
			} else {
				self::dic()->ctrl()->redirect($this, self::CMD_SHOW_FORM);
			}
		} else {
			self::dic()->ui()->mainTemplate()->setContent($xoctWorkflowParameterFormGUI->getHTML());
		}
	}


	/**
	 *
	 */
	protected function updateParameter() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this);
		$xoctWorkflowParameterFormGUI->setValuesByPost();
		if ($xoctWorkflowParameterFormGUI->storeForm()) {
			ilUtil::sendSuccess(self::plugin()->translate('config_msg_success'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_SHOW_TABLE);
		}
		self::dic()->ui()->mainTemplate()->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 */
	protected function updateTable() {
		foreach (filter_input(INPUT_POST, 'workflow_parameter', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) as $id => $value) {
			$default_value_admin = $value['default_value_admin'];
			$default_value_member = $value['default_value_member'];
			if (in_array($default_value_member, WorkflowParameter::$possible_values) && in_array($default_value_admin, WorkflowParameter::$possible_values)) {
				WorkflowParameter::find($id)->setDefaultValueAdmin($default_value_admin)->setDefaultValueMember($default_value_member)->update();
			}
		}
		ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
		self::dic()->ctrl()->redirect($this, self::CMD_SHOW_TABLE);
	}


	/**
	 *
	 */
	protected function overwriteSeriesParameters() {
		WorkflowParameterRepository::getInstance()->overwriteSeriesParameter();
		ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
		self::dic()->ctrl()->redirect($this, self::CMD_SHOW_FORM);
	}

	/**
	 *
	 */
	protected function confirmDelete() {
		WorkflowParameterRepository::getInstance()->deleteById($_POST['param_id']);
		ilUtil::sendSuccess(self::plugin()->translate('config_msg_success'), true);
		self::dic()->ctrl()->redirect($this, self::CMD_SHOW_TABLE);
	}


	/**
	 *
	 */
	protected function delete() {
		$ilConfirmationGUI = new ilConfirmationGUI();
		$ilConfirmationGUI->setFormAction(self::dic()->ctrl()->getFormAction($this));
		$ilConfirmationGUI->setConfirm(self::dic()->language()->txt('confirm'), self::CMD_CONFIRM);
		$ilConfirmationGUI->setCancel(self::dic()->language()->txt('cancel'), self::CMD_SHOW_TABLE);
		$ilConfirmationGUI->addItem('param_id', $_GET['param_id'], WorkflowParameter::find($_GET['param_id'])->getTitle());
		$ilConfirmationGUI->setHeaderText(self::plugin()->translate('msg_confirm_delete_param'));
		self::dic()->ui()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
	}


	/**
	 *
	 */
	public function setOverwriteSeriesParameter() {
		$this->overwrite_series_parameters = true;
	}
}