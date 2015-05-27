<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('class.xoctSystemAccountTableGUI.php');
require_once('class.xoctSystemAccountFormGUI.php');
require_once('./Services/UIComponent/Button/classes/class.ilLinkButton.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');

/**
 * Class xoctSystemAccountGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctSystemAccountGUI: xoctMainGUI
 */
class xoctSystemAccountGUI extends xoctGUI {

	const IDENTIFIER = 'dm';


	protected function index() {
		$b = ilLinkButton::getInstance();
		$b->setCaption($this->pl->getPrefix() . '_system_account_add_new');
		$b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
		$this->toolbar->addButtonInstance($b);
		$xoctSystemAccountTableGUI = new xoctSystemAccountTableGUI($this, self::CMD_STANDARD);
		$this->tpl->setContent($xoctSystemAccountTableGUI->getHTML());
	}


	protected function add() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, new xoctSystemAccount());
		$this->tpl->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	protected function create() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, new xoctSystemAccount());
		$xoctSystemAccountFormGUI->setValuesByPost();
		if ($xoctSystemAccountFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('system_account_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	protected function edit() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, xoctSystemAccount::find($_GET[self::IDENTIFIER]));
		$xoctSystemAccountFormGUI->fillForm();
		$this->tpl->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	protected function update() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, xoctSystemAccount::find($_GET[self::IDENTIFIER]));
		$xoctSystemAccountFormGUI->setValuesByPost();
		if ($xoctSystemAccountFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('system_account_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	public function txt($key) {
		return $this->pl->txt('system_account_' . $key);
	}


	protected function confirmDelete() {
		/**
		 * @var $xoctSystemAccount xoctSystemAccount
		 */
		$xoctSystemAccount = xoctSystemAccount::find($_GET[self::IDENTIFIER]);
		$confirm = new ilConfirmationGUI();
		$confirm->addItem(self::IDENTIFIER, $xoctSystemAccount->getDomain(), $xoctSystemAccount->getExtId());
		$confirm->setFormAction($this->ctrl->getFormAction($this));
		$confirm->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
		$confirm->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE);

		$this->tpl->setContent($confirm->getHTML());
	}


	protected function delete() {
		/**
		 * @var $xoctSystemAccount xoctSystemAccount
		 */
		$xoctSystemAccount = xoctSystemAccount::find($_POST[self::IDENTIFIER]);
		$xoctSystemAccount->delete();
		$this->cancel();
	}
}