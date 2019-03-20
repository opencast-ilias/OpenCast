<?php

/**
 * Class xoctWorkflowParameterGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowParameterGUI : xoctMainGUI
 */
class xoctWorkflowParameterGUI extends xoctGUI {

	const CMD_SHOW_TABLE = 'showTable';
	const CMD_UPDATE_FORM = 'updateForm';
	const CMD_UPDATE_PARAMETER = 'updateParameter';
	const CMD_LOAD_WORKFLOW_PARAMS = 'loadWorkflowParameters';
	const CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED = 'loadWorkflowParametersConfirmed';

	/**
	 *
	 */
	protected function index() {
		$this->tabs->setSubTabActive(xoctMainGUI::SUBTAB_WORKFLOW_PARAMETERS);
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function showTable() {
		$this->tabs->clearSubTabs();
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, self::CMD_STANDARD));
		$xoctWorkflowParameterTableGUI = new xoctWorkflowParameterTableGUI($this, self::CMD_SHOW_TABLE);
		$this->tpl->setContent($xoctWorkflowParameterTableGUI->getHTML());
	}

	/**
	 * @param $cmd
	 */
	protected function performCommand($cmd) {
		$this->initToolbar($cmd);
		$this->{$cmd}();
	}

	/**
	 *
	 */
	protected function initToolbar($cmd) {
		switch ($cmd) {
			case self::CMD_STANDARD:
				$load_params = ilLinkButton::getInstance();
				$load_params->setCaption($this->pl->txt('config_btn_edit_parameters_manually'), false);
				$load_params->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SHOW_TABLE));
				$this->toolbar->addButtonInstance($load_params);
				break;
			case self::CMD_SHOW_TABLE:
				$load_params = ilLinkButton::getInstance();
				$load_params->setCaption($this->pl->txt('config_btn_load_parameters'), false);
				$load_params->setUrl($this->ctrl->getLinkTarget($this, self::CMD_LOAD_WORKFLOW_PARAMS));
				$this->toolbar->addButtonInstance($load_params);
				$load_params = ilLinkButton::getInstance();
				$load_params->setCaption($this->pl->txt('config_btn_add_parameter'), false);
				$load_params->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
				$this->toolbar->addButtonInstance($load_params);
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
			$params = xoctWorkflowParameterRepository::getInstance()->loadParametersFromAPI();
			$ilConfirmationGUI = new ilConfirmationGUI();
			$ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
			$ilConfirmationGUI->setCancel($this->lng->txt('cancel'), self::CMD_STANDARD);
			$ilConfirmationGUI->setConfirm($this->lng->txt('confirm'), self::CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED);
			/** @var xoctWorkflowParameter $param */
			foreach ($params as $param) {
				$ilConfirmationGUI->addItem('workflow_params[' . $param->getId() . '][title]', $param->getTitle(), $param->getTitle());
				$ilConfirmationGUI->addHiddenItem('workflow_params[' . $param->getId() . '][type]', $param->getType());
			}
			$this->tpl->setContent($ilConfirmationGUI->getHTML());
		} catch (xoctException $e) {
			ilUtil::sendFailure($e->getMessage());
			$this->index();
		}
	}


	/**
	 *
	 */
	protected function loadWorkflowParametersConfirmed() {
		$existing_ids = xoctWorkflowParameter::getArray(null, 'id');
		$delivered_params = filter_input(INPUT_POST, 'workflow_params', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		$delivered_ids = array_keys($delivered_params);
		$to_delete_ids = array_diff($existing_ids, $delivered_ids);
		$to_create_ids = array_diff($delivered_ids, $existing_ids);
		$to_create = [];

		// create new and update existing
		foreach ($delivered_params as $param_id => $parameter) {
			$xoctWorkflowParameter = xoctWorkflowParameterRepository::getInstance()->createOrUpdate($param_id, $parameter['title'], $parameter['type']);
			if (in_array($param_id, $to_create_ids)) {
				$to_create[] = $xoctWorkflowParameter;
			}
		}

		// delete not delivered
		foreach ($to_delete_ids as $id_to_delete) {
			xoctWorkflowParameter::find($id_to_delete)->delete();
		}

		// create/delete the series settings
		if ($to_delete_ids) {
			xoctSeriesWorkflowParameterRepository::getInstance()->deleteParamsForAllObjectsById($to_delete_ids);
		}
		if ($to_create_ids) {
			xoctSeriesWorkflowParameterRepository::getInstance()->createParamsForAllObjects($to_create);
		}

		ilUtil::sendSuccess($this->lng->txt('config_msg_success'));
		$this->index();
	}


	/**
	 *
	 */
	protected function edit() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this, filter_input(INPUT_GET, 'param_id'));
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function add() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this);
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
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
			ilUtil::sendSuccess($this->pl->txt('config_msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function updateParameter() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this);
		$xoctWorkflowParameterFormGUI->setValuesByPost();
		if ($xoctWorkflowParameterFormGUI->storeForm()) {
			ilUtil::sendSuccess($this->pl->txt('config_msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
		}
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}

	/**
	 *
	 */
	protected function confirmDelete() {
		xoctWorkflowParameterRepository::getInstance()->deleteById($_POST['param_id']);
		ilUtil::sendSuccess($this->pl->txt('msg_success'));
		$this->showTable();
	}


	/**
	 *
	 */
	protected function delete() {
		$ilConfirmationGUI = new ilConfirmationGUI();
		$ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
		$ilConfirmationGUI->setConfirm($this->lng->txt('confirm'), self::CMD_CONFIRM);
		$ilConfirmationGUI->setCancel($this->lng->txt('cancel'), self::CMD_SHOW_TABLE);
		$ilConfirmationGUI->addItem('param_id', $_GET['param_id'], xoctWorkflowParameter::find($_GET['param_id'])->getTitle());
		$ilConfirmationGUI->setHeaderText($this->pl->txt('msg_confirm_delete'));
		$this->tpl->setContent($ilConfirmationGUI->getHTML());
	}
}