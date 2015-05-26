<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctGroupParticipant.php');

/**
 * Class xoctGroupParticipantGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrl_IsCalledBy xoctGroupParticipantGUI:ilObjOpenCastGUI
 */
class xoctGroupParticipantGUI extends xoctGUI {

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
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_GROUPS);
		xoctWaiterGUI::init();
		$this->tpl->addJavaScript($this->pl->getStyleSheetLocation('default/group_participants.js'));
	}


	/**
	 * @param $data
	 */
	protected function outJson($data) {
		header('Content-type: application/json');
		echo json_encode($data);
		exit;
	}


	protected function index() {
	}


	protected function getAvailable() {
		xoctGroupParticipant::getAvailable($_GET['ref_id']);
	}


	protected function add() {
		// TODO: Implement add() method.
	}


	protected function create() {
		// TODO: Implement create() method.
	}


	protected function edit() {
		// TODO: Implement edit() method.
	}


	protected function update() {
		// TODO: Implement update() method.
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		// TODO: Implement delete() method.
	}
}