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


	public function executeCommand() {
		if (!ilObjOpenCastAccess::hasWriteAccess()) {
			$this->ctrl->redirectByClass('xoctEventGUI');
		}
		parent::executeCommand();
	}


	protected function index() {
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_EVENTS);
	}


	protected function add() {
		// TODO: Implement add() method.
	}


	protected function create() {
		// TODO: Implement create() method.
	}


	protected function edit() {
		if ($this->xoctOpenCast->getDuplicatesOnSystem()) {
			ilUtil::sendInfo($this->pl->txt('series_has_duplicates'));
		}
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_SETTINGS);
		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		$xoctSeriesFormGUI->fillForm();
		$this->tpl->setContent($xoctSeriesFormGUI->getHTML());
	}


	protected function update() {
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_SETTINGS);
		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		if ($xoctSeriesFormGUI->saveObject()) {
			$obj = new ilObjOpenCast($_GET['ref_id']);
			$obj->setTitle($this->xoctOpenCast->getSeries()->getTitle());
			$obj->setDescription($this->xoctOpenCast->getSeries()->getDescription());
			$obj->update();
			ilUtil::sendSuccess($this->pl->txt('series_saved'), true);
			$this->ctrl->redirect($this, self::CMD_EDIT);
		}
		$xoctSeriesFormGUI->setValuesByPost();
		$this->tpl->setContent($xoctSeriesFormGUI->getHTML());
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		// TODO: Implement delete() method.
	}


	protected function view() {
	}


	protected function cancel() {
		$this->ctrl->redirectByClass('xoctEventGUI', xoctEventGUI::CMD_STANDARD);
	}
}