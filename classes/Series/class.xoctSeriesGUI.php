<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctSeriesFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctSeries.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');

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
	public function __construct(xoctOpenCast $xoctOpenCast = NULL) {
		parent::__construct();
		if ($xoctOpenCast instanceof xoctOpenCast) {
			$this->xoctOpenCast = $xoctOpenCast;
		} else {
			$this->xoctOpenCast = new xoctOpenCast ();
		}
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
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_SETTINGS);
		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		$xoctSeriesFormGUI->fillForm();
		$this->tpl->setContent($xoctSeriesFormGUI->getHTML());
	}


	protected function update() {
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_SETTINGS);
		$xoctSeriesFormGUI = new xoctSeriesFormGUI($this, $this->xoctOpenCast);
		if ($xoctSeriesFormGUI->saveObject()) {
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

	protected function view(){

	}
}