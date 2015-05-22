<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('class.xoctGroup.php');

/**
 * Class xoctGroupGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctGroupGUI: ilObjOpenCastGUI
 */
class xoctGroupGUI extends xoctGUI {

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
		//		xoctGroup::installDB();
	}


	protected function index() {
		$this->tpl->addCss($this->pl->getStyleSheetLocation('default/groups.css'));
		$temp = $this->pl->getTemplate('default/tpl.groups.html', false, false);
		$temp->setVariable('HEADER_GROUPS', $this->pl->txt('groups_header'));
		$temp->setVariable('HEADER_PARTICIPANTS', $this->pl->txt('groups_participants_header'));
		$temp->setVariable('L_GROUP_NAME', $this->pl->txt('groups_new'));
		$temp->setVariable('PH_GROUP_NAME', $this->pl->txt('groups_new_placeholder'));
		$temp->setVariable('L_FILTER', $this->pl->txt('groups_participants_filter'));
		$temp->setVariable('PH_FILTER', $this->pl->txt('groups_participants_filter_placeholder'));
		$temp->setVariable('BUTTON_GROUP_NAME', $this->pl->txt('groups_new_button'));
		$temp->setVariable('BASE_URL', ($this->ctrl->getLinkTarget($this, '', '', true)));

		$groups = xoctGroup::getAllForObjId($this->xoctOpenCast->getObjId());

		//SET ajax links
		$this->tpl->setContent($temp->get());

		return;

		//
		//
		//		$temp->setCurrentBlock('javascript');
		//		$temp->setVariable('NEW_GROUP_LINK', $this->ctrl->getLinkTarget($this, 'newGroup'));
		//		$temp->setVariable('DELETE_GROUP_LINK', $this->ctrl->getLinkTarget($this, 'deleteGroup'));
		//		$temp->setVariable('ADD_MEMBER_LINK', $this->ctrl->getLinkTarget($this, 'addToGroup'));
		//		$temp->setVariable('REMOVE_MEMBER_LINK', $this->ctrl->getLinkTarget($this, 'removeFromGroup'));
		//		$temp->setVariable('DELETE_GROUP_CONFIRMATION', $this->pl->txt('delete_group_confirmation'));
		//		$temp->parseCurrentBlock();
		//		// SET BOX WITH GROUPS
		//		$temp->setCurrentBlock('group');
		//		$temp->setVariable('GROUPS', $this->pl->txt('groups'));
		//		$temp->setVariable('SELECT_A_GROUP', $this->pl->txt('select_a_group'));
		//		$temp->setVariable('CREATE_A_GROUP', $this->pl->txt('create_a_group'));
		//		foreach ($groups as $group) {
		//			$gt = $this->pl->getTemplate('default/tpl.groups.html');
		//			$this->buildGroupTemplate($gt, $group);
		//			$temp->setCurrentBlock('groupplace');
		//			$temp->setVariable('GROUP_PLACE', $gt->get());
		//			$temp->parseCurrentBlock();
		//		}
		//		$temp->parseCurrentBlock();
		//		// SET BOX WITH PARTICIPANTS
		//		$temp->setCurrentBlock('participants');
		//		$temp->setVariable('PARTICIPANTS', $this->pl->txt('available_participants'));
		//		//Mirglieder sortiert ausgeben.
		//		foreach ($this->participants->getParticipants() as $participant) {
		//			$participant = new ilObjUser($participant);
		//			$arr_participant[$participant->getFullname()]['fullname'] = $participant->getFullname();
		//			$arr_participant[$participant->getFullname()]['email'] = $participant->getEmail();
		//			$arr_participant[$participant->getFullname()]['id'] = $participant->getId();
		//			$arr_participant[$participant->getFullname()]['image'] = $participant->getPersonalPicturePath('xsmall');
		//		}
		//		@asort($arr_participant);
		//		foreach ($arr_participant as $participant) {
		//			$temp->setCurrentBlock('participant');
		//			$temp->setVariable('PARTICIPANT', $participant['fullname']);
		//			$temp->setVariable('PARTICIPANT_EMAIl', $participant['email']);
		//			$temp->setVariable('PARTICIPANT_ID', $participant['id']);
		//			$temp->setVariable('PARTICIPANT_ADD', $this->pl->txt('add'));
		//			// GET USER IMAGE
		//			$temp->setVariable('PARTICIPANT_IMAGE', $participant['image']);
		//			$temp->parseCurrentBlock();
		//		}
		//		$temp->parseCurrentBlock();
		//

	}


	public function getAll() {
		header('Content-type: application/json');
		$arr = array();
		foreach (xoctGroup::getAllForObjId($this->xoctOpenCast->getObjId()) as $group) {
			$arr[] = $group->__asStdClass();
		}

		echo json_encode($arr);
		exit;
	}


	/**
	 * @param $tpl   ilTemplate
	 * @param $group xscaGroup
	 */
	private function buildGroupTemplate(&$tpl, $group) {
		$tpl->setCurrentBlock('group');
		$tpl->setVariable('GROUP', $this->pl->txt('group'));
		$tpl->setVariable('GROUP_NAME', $group->getTitle());
		$tpl->setVariable('GROUP_ID', $group->getId());
		//Gruppenmitglieder sortieren
		if ($group->getMemberIds()) {
			foreach ($group->getMemberIds() as $member_id) {
				$user = new ilObjUser($member_id);
				$arr_members[$user->getFullname()] = $member_id;
			}
		}
		if ($arr_members) {
			@arsort($arr_members);
			foreach ($arr_members as $member_id) {
				$mt = $this->pl->getTemplate('default/tpl.groups.html');
				$this->buildMemberTemplate($mt, $member_id);
				$tpl->setCurrentBlock('memberplace');
				$tpl->setVariable('MEMBER_PLACE', $mt->get());
				$tpl->parseCurrentBlock();
			}
		}
		$tpl->parseCurrentBlock();
	}


	/**
	 * @param $tpl       ilTemplate
	 * @param $member_id int
	 */
	private function buildMemberTemplate(&$tpl, $member_id) {
		$tpl->setCurrentBlock('member');
		$user = new ilObjUser($member_id);
		$tpl->setVariable('PARTICIPANT', $user->getFullname());
		$tpl->setVariable('PARTICIPANT_EMAIl', $user->getEmail());
		$tpl->setVariable('PARTICIPANT_ID', $user->getId());
		$tpl->setVariable('PARTICIPANT_REMOVE', $this->pl->txt('remove'));
		$tpl->parseCurrentBlock();
	}


	//
	// AJAX Methods
	//
	public function newGroup() {
		$name = $_GET['groupName'];
		$group = xscaGroup::getInstance();
		$group->setTitle($name);
		$group->setScastId($this->scast->getId());
		$group->create();
		$tpl = $this->pl->getTemplate('default/tpl.groups.html');
		$this->buildGroupTemplate($tpl, $group);
		echo $tpl->get();
		exit;
	}


	public function deleteGroup() {
		$group = xscaGroup::getInstance($_GET['groupId']);
		$group->delete();
	}


	public function addToGroup() {
		$group_id = $_GET['groupId'];
		$participant_id = $_GET['participantId'];
		$group = xscaGroup::getInstance($group_id);
		$newly_added = $group->addMemberById($participant_id);
		$tpl = $this->pl->getTemplate('default/tpl.groups.html');
		$this->buildMemberTemplate($tpl, $participant_id);
		if ($newly_added) {
			echo $tpl->get();
		}
		exit;
	}


	public function removeFromGroup() {
		$group_id = $_GET['groupId'];
		$member_id = $_GET['memberId'];
		$group = xscaGroup::getInstance($group_id);
		$group->removeMemberById($member_id);
	}


	protected function add() {
		// TODO: Implement add() method.
	}


	protected function create() {
		header('Content-type: application/json');
		$obj = new xoctGroup();
		$obj->setSerieId($this->xoctOpenCast->getObjId());
		$obj->setTitle($_POST['title']);
		$obj->create();
		echo json_encode($obj->__asStdClass());
		exit;
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
		header('Content-type: application/json');
		/**
		 * @var $xoctGroup xoctGroup
		 */
		$status = false;
		$xoctGroup = xoctGroup::find($_GET['id']);
		if ($xoctGroup->getSerieId() == $this->xoctOpenCast->getObjId()) {
			$xoctGroup->delete();
			$status = true;
		}
		echo json_encode($status);
		exit;
	}
}