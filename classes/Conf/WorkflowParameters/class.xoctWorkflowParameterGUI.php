<?php

/**
 * Class xoctWorkflowParameterGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowParameterGUI : xoctMainGUI
 */
class xoctWorkflowParameterGUI extends xoctGUI {

	const CMD_LOAD_WORKFLOW_PARAMS = 'loadWorkflowParameters';
	const CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED = 'loadWorkflowParametersConfirmed';


	/**
	 *
	 */
	protected function index() {
		$this->initToolbar();
		$this->tabs->setSubTabActive(xoctMainGUI::SUBTAB_WORKFLOW_PARAMETERS);
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this);
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function initToolbar() {
		$load_params = ilLinkButton::getInstance();
		$load_params->setCaption($this->pl->txt('config_btn_load_parameters'), false);
		$load_params->setUrl($this->ctrl->getLinkTarget($this, self::CMD_LOAD_WORKFLOW_PARAMS));
		$this->toolbar->addButtonInstance($load_params);
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
				$ilConfirmationGUI->addHiddenItem('workflow_params[' . $param->getId() . '][title]', $param->getTitle());
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
			/** @var xoctWorkflowParameter $xoctWorkflowParameter */
			$xoctWorkflowParameter = xoctWorkflowParameter::findOrGetInstance($param_id);
			$xoctWorkflowParameter->setTitle($parameter['title']);
			$xoctWorkflowParameter->setType($parameter['type']);
			$xoctWorkflowParameter->store();
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

		ilUtil::sendSuccess($this->lng->txt('msg_success'));
		$this->index();
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
	protected function editGeneral() {
	}


	/**
	 *
	 */
	protected function update() {
		$xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this);
		$xoctWorkflowParameterFormGUI->setValuesByPost();
		if ($xoctWorkflowParameterFormGUI->storeForm()) {
			ilUtil::sendSuccess($this->pl->txt('msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$this->tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
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
}