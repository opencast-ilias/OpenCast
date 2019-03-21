<?php
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
		$b->setCaption(self::plugin()->getPluginObject()->getPrefix() . '_system_account_add_new');
		$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD));
		self::dic()->toolbar()->addButtonInstance($b);
		$xoctSystemAccountTableGUI = new xoctSystemAccountTableGUI($this, self::CMD_STANDARD);
		self::dic()->mainTemplate()->setContent($xoctSystemAccountTableGUI->getHTML());
	}


	protected function add() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, new xoctSystemAccount());
		self::dic()->mainTemplate()->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	protected function create() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, new xoctSystemAccount());
		$xoctSystemAccountFormGUI->setValuesByPost();
		if ($xoctSystemAccountFormGUI->saveObject()) {
			ilUtil::sendSuccess(self::plugin()->translate('system_account_msg_success'), true);
			self::dic()->ctrl()->redirect($this);
		}
		self::dic()->mainTemplate()->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	protected function edit() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, xoctSystemAccount::find($_GET[self::IDENTIFIER]));
		$xoctSystemAccountFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	protected function update() {
		$xoctSystemAccountFormGUI = new xoctSystemAccountFormGUI($this, xoctSystemAccount::find($_GET[self::IDENTIFIER]));
		$xoctSystemAccountFormGUI->setValuesByPost();
		if ($xoctSystemAccountFormGUI->saveObject()) {
			ilUtil::sendSuccess(self::plugin()->translate('system_account_msg_success'), true);
			self::dic()->ctrl()->redirect($this);
		}
		self::dic()->mainTemplate()->setContent($xoctSystemAccountFormGUI->getHTML());
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	public function txt($key) {
		return self::plugin()->translate('system_account_' . $key);
	}


	protected function confirmDelete() {
		/**
		 * @var $xoctSystemAccount xoctSystemAccount
		 */
		$xoctSystemAccount = xoctSystemAccount::find($_GET[self::IDENTIFIER]);
		$confirm = new ilConfirmationGUI();
		$confirm->addItem(self::IDENTIFIER, $xoctSystemAccount->getDomain(), $xoctSystemAccount->getExtId());
		$confirm->setFormAction(self::dic()->ctrl()->getFormAction($this));
		$confirm->setCancel($this->txt(self::CMD_CANCEL), self::CMD_CANCEL);
		$confirm->setConfirm($this->txt(self::CMD_DELETE), self::CMD_DELETE);

		self::dic()->mainTemplate()->setContent($confirm->getHTML());
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