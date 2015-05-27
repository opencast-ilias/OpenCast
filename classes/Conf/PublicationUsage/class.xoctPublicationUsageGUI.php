<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('class.xoctPublicationUsage.php');
require_once('class.xoctPublicationUsageTableGUI.php');
require_once('class.xoctPublicationUsageFormGUI.php');

/**
 * Class xoctPublicationUsageGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPublicationUsageGUI : xoctMainGUI
 */
class xoctPublicationUsageGUI extends xoctGUI {

	const IDENTIFIER = 'usage_id';
	const CMD_SELECT_PUBLICATION_ID = 'selectPublicationId';


	protected function index() {
		if(count(xoctPublicationUsage::getMissingUsageIds()) > 0) {
			$b = ilLinkButton::getInstance();
			$b->setCaption($this->pl->getPrefix() . '_publication_usage_add_new');
			$b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SELECT_PUBLICATION_ID));
			$this->toolbar->addButtonInstance($b);
		}
		$xoctPublicationUsageTableGUI = new xoctPublicationUsageTableGUI($this, self::CMD_STANDARD);
		$this->tpl->setContent($xoctPublicationUsageTableGUI->getHTML());
	}


	protected function selectPublicationId() {
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->txt('select_usage_id'));
		$form->addCommandButton(self::CMD_ADD, $this->txt(self::CMD_ADD));
		$form->addCommandButton(self::CMD_CANCEL, $this->txt(self::CMD_CANCEL));
		$sel = new ilSelectInputGUI($this->txt(xoctPublicationUsageFormGUI::F_PUBLICATION_ID), xoctPublicationUsageFormGUI::F_PUBLICATION_ID);
		$options = array();
		foreach (xoctPublicationUsage::getMissingUsageIds() as $id) {
			$options[$id] = $this->txt('type_' . $id);
		}
		$sel->setOptions($options);

		$form->addItem($sel);
		$this->tpl->setContent($form->getHTML());
	}


	protected function add() {
		if (! $_POST[xoctPublicationUsageFormGUI::F_PUBLICATION_ID]) {
			$this->ctrl->redirect($this, self::CMD_SELECT_PUBLICATION_ID);
		}
		$xoctPublicationUsage = new xoctPublicationUsage();
		$xoctPublicationUsage->setUsageId($_POST[xoctPublicationUsageFormGUI::F_PUBLICATION_ID]);
		$xoctPublicationUsage->setTitle($this->txt('type_' . $_POST[xoctPublicationUsageFormGUI::F_PUBLICATION_ID]));
		$xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, $xoctPublicationUsage);
		$xoctPublicationUsageFormGUI->fillForm();
		$this->tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
	}


	protected function create() {
		$xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, new xoctPublicationUsage());
		$xoctPublicationUsageFormGUI->setValuesByPost();
		if ($xoctPublicationUsageFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('publication_usage_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
	}


	protected function edit() {
		$xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, xoctPublicationUsage::find($_GET[self::IDENTIFIER]));
		$xoctPublicationUsageFormGUI->fillForm();
		$this->tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
	}


	protected function update() {
		$xoctPublicationUsageFormGUI = new xoctPublicationUsageFormGUI($this, xoctPublicationUsage::find($_GET[self::IDENTIFIER]));
		$xoctPublicationUsageFormGUI->setValuesByPost();
		if ($xoctPublicationUsageFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('publication_usage_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctPublicationUsageFormGUI->getHTML());
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	public function txt($key) {
		return $this->pl->txt('publication_usage_' . $key);
	}


	protected function confirmDelete() {
		/**
		 * @var $xoctPublicationUsage xoctPublicationUsage
		 */
		$xoctPublicationUsage = xoctPublicationUsage::find($_GET[self::IDENTIFIER]);
		$confirm = new ilConfirmationGUI();
		$confirm->addItem(self::IDENTIFIER, $xoctPublicationUsage->getUsageId(), $xoctPublicationUsage->getTitle());
		$confirm->setFormAction($this->ctrl->getFormAction($this));
		$confirm->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
		$confirm->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE);

		$this->tpl->setContent($confirm->getHTML());
	}


	protected function delete() {
		/**
		 * @var $xoctPublicationUsage xoctPublicationUsage
		 */
		$xoctPublicationUsage = xoctPublicationUsage::find($_POST[self::IDENTIFIER]);
		$xoctPublicationUsage->delete();
		$this->cancel();
	}
}