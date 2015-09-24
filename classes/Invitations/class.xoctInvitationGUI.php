<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('class.xoctInvitation.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCast.php');

/**
 * Class xoctInvitationGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctInvitationGUI: ilObjOpenCastGUI
 */
class xoctInvitationGUI extends xoctGUI {

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
		$this->tabs->setTabActive(ilObjOpenCastGUI::TAB_EVENTS);
		xoctWaiterGUI::init();
		$this->tpl->addJavaScript($this->pl->getStyleSheetLocation('default/invitations.js'));
	}


	protected function index() {
		$xoctEvent = xoctEvent::find($_GET[xoctEventGUI::IDENTIFIER]);
		$temp = $this->pl->getTemplate('default/tpl.invitations.html', false, false);
		$temp->setVariable('PREVIEW', xoctSecureLink::sign($xoctEvent->getThumbnailUrl()));
		$temp->setVariable('VIDEO_TITLE', $xoctEvent->getTitle());
		$temp->setVariable('HEADER_INVITAIONS', $this->pl->txt('invitations_header'));
		$temp->setVariable('HEADER_PARTICIPANTS_AVAILABLE', $this->pl->txt('groups_available_participants_header'));
		$temp->setVariable('BASE_URL', ($this->ctrl->getLinkTarget($this, '', '', true)));
		$temp->setVariable('INVITATION_LANGUAGE', json_encode(array(
			'no_title' => $this->pl->txt('invitation_alert_no_title'),
			'delete_group' => $this->pl->txt('invitation_alert_delete_group'),
			'none_available' => $this->pl->txt('invitation_none_available')
		)));
		$this->tpl->setContent($temp->get());
	}


	/**
	 * @param $data
	 */
	protected function outJson($data) {
		header('Content-type: application/json');
		echo json_encode($data);
		exit;
	}


	protected function add() {
		// TODO: Implement add() method.
	}


	public function getAll() {
		global $ilUser;
		/**
		 * @var $xoctUser xoctUser
		 */
		$xoctUsers = array();
		$course_members_user_ids = $this->getCourseMembers();
		foreach ($course_members_user_ids as $user_id) {
			$xoctUsers[$user_id] = xoctUser::getInstance(new ilObjUser($user_id));
		}
		$invited_user_ids = xoctInvitation::where(array(
			'obj_id' => $this->xoctOpenCast->getObjId(),
			'owner_id' => $ilUser->getId()
		))->getArray(NULL, 'user_id');

		$available_user_ids = array_diff($course_members_user_ids, $invited_user_ids);
		$invited_users = array();
		$available_users = array();
		foreach ($available_user_ids as $user_id) {
			$user = new stdClass();
			$xoctUser = $xoctUsers[$user_id];
			$user->id = $user_id;
			$user->name = $xoctUser->getNamePresentation();
			$available_users[] = $user;
		}

		foreach ($invited_user_ids as $user_id) {
			$user = new stdClass();
			$xoctUser = $xoctUsers[$user_id];
			$user->id = $user_id;
			$user->name = $xoctUser->getNamePresentation();
			$invited_users[] = $user;
		}

		$arr = array(
			'invited' => $invited_users,
			'available' => $available_users,
		);

		$this->outJson($arr);
	}


	/**
	 * @return array
	 */
	protected function getCourseMembers() {
		$ref_id = ilObjOpenCast::returnParentCrsRefId($_GET['ref_id']);
		$p = new ilCourseParticipants(ilObject2::_lookupObjId($ref_id));

		return $p->getMembers();
	}


	protected function create() {
		global $ilUser;
		$obj = xoctInvitation::where(array(
			'obj_id' => $this->xoctOpenCast->getObjId(),
			'user_id' => $_POST['id'],
			'owner_id' => $ilUser->getId()
		))->first();
		$new = false;
		if (! $obj instanceof xoctInvitation) {
			$obj = new xoctInvitation();
			$new = true;
		}
		$obj->setObjId($this->xoctOpenCast->getObjId());
		$obj->setUserId($_POST['id']);
		$obj->setOwnerId($ilUser->getId());
		if ($new) {
			$obj->create();
		} else {
			$obj->update();
		}
		$this->outJson($obj->__asStdClass());
	}


	protected function edit() {
	}


	protected function update() {
		// TODO: Implement update() method.
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		global $ilUser;
		$obj = xoctInvitation::where(array(
			'obj_id' => $this->xoctOpenCast->getObjId(),
			'user_id' => $_POST['id'],
			'owner_id' => $ilUser->getId()
		))->first();
		if ($obj instanceof xoctInvitation) {
			$obj->delete();
		}
	}
}