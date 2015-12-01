<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctGroupParticipant.php');
require_once('class.xoctUser.php');

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
		xoctWaiterGUI::loadLib();
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
		$data = array();

		foreach (xoctGroupParticipant::getAvailable($_GET['ref_id']) as $xoctGroupParticipant) {
			$stdClass = $xoctGroupParticipant->__asStdClass();
			$stdClass->display_name = $xoctGroupParticipant->getXoctUser()->getNamePresentation();
			$data[] = $stdClass;
		}

		$this->outJson($data);
	}


	protected function getPerGroup() {
		$data = array();
		$group_id = $_GET['group_id'];
		if (! $group_id) {
			$this->outJson(NULL);
		}
		foreach (xoctGroupParticipant::where(array( 'group_id' => $group_id ))->get() as $xoctGroupParticipant) {
			$stdClass = $xoctGroupParticipant->__asStdClass();
			$stdClass->display_name = $xoctGroupParticipant->getXoctUser()->getNamePresentation();
			$data[] = $stdClass;
		}
		$this->outJson($data);
	}


	protected function add() {
		// TODO: Implement add() method.
	}


	protected function create() {
		if (! $_POST['user_id'] OR ! $_POST['group_id']) {
			$this->outJson(false);
		}
		$xoctGroupParticipant = new xoctGroupParticipant();
		$xoctGroupParticipant->setUserId($_POST['user_id']);
		$xoctGroupParticipant->setGroupId($_POST['group_id']);
		$xoctGroupParticipant->create();
		$this->outJson(true);
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
		if (! $_POST['id']) {
			$this->outJson(false);
		}
		$o = new xoctGroupParticipant($_POST['id']);
		$o->delete();
		$this->outJson(true);
	}
}