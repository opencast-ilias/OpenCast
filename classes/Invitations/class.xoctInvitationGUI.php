<?php
/**
 * Class xoctInvitationGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctInvitationGUI: ilObjOpenCastGUI
 */
class xoctInvitationGUI extends xoctGUI {

    /**
     * @var xoctEvent
     */
    protected $xoctEvent;
    /**
     * @var xoctOpenCast
     */
    protected $xoctOpenCast;

	/**
	 * @param xoctOpenCast $xoctOpenCast
	 */
	public function __construct(xoctOpenCast $xoctOpenCast = NULL) {
		if ($xoctOpenCast instanceof xoctOpenCast) {
			$this->xoctOpenCast = $xoctOpenCast;
		} else {
			$this->xoctOpenCast = new xoctOpenCast();
		}
		$this->xoctEvent = xoctEvent::find($_GET[xoctEventGUI::IDENTIFIER]);
		self::dic()->tabs()->clearTargets();


		self::dic()->tabs()->setBackTarget(self::plugin()->translate('tab_back'), self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class));
		xoctWaiterGUI::loadLib();
		self::dic()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getStyleSheetLocation('default/invitations.css'));
		self::dic()->mainTemplate()->addJavaScript(self::plugin()->getPluginObject()->getStyleSheetLocation('default/invitations.js'));
		self::dic()->ctrl()->saveParameter($this, xoctEventGUI::IDENTIFIER);
	}


	protected function index() {
		$xoctUser = xoctUser::getInstance(self::dic()->user());
		if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SHARE_EVENT, $this->xoctEvent, $xoctUser, $this->xoctOpenCast)) {
			ilUtil::sendFailure('Access denied', true);
			self::dic()->ctrl()->redirectByClass(xoctEventGUI::class);
		}
		$temp = self::plugin()->getPluginObject()->getTemplate('default/tpl.invitations.html', false, false);
		$temp->setVariable('PREVIEW', $this->xoctEvent->getThumbnailUrl());
		$temp->setVariable('VIDEO_TITLE', $this->xoctEvent->getTitle());
        $temp->setVariable('L_FILTER', self::plugin()->translate('groups_participants_filter'));
        $temp->setVariable('PH_FILTER', self::plugin()->translate('groups_participants_filter_placeholder'));
        $temp->setVariable('HEADER_INVITAIONS', self::plugin()->translate('invitations_header'));
		$temp->setVariable('HEADER_PARTICIPANTS_AVAILABLE', self::plugin()->translate('groups_available_participants_header'));
		$temp->setVariable('BASE_URL', (self::dic()->ctrl()->getLinkTarget($this, '', '', true)));
		$temp->setVariable('LANGUAGE', json_encode(array(
			'none_available' => self::plugin()->translate('invitations_none_available')
		)));
		self::dic()->mainTemplate()->setContent($temp->get());
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
	}


	public function getAll() {
		/**
		 * @var $xoctUser xoctUser
		 */
		$xoctUsers = array();
		$course_members_user_ids = $this->getCourseMembers();
		foreach ($course_members_user_ids as $user_id) {
			$xoctUsers[$user_id] = xoctUser::getInstance(new ilObjUser($user_id));
		}
		$active_invitations = xoctInvitation::getActiveInvitationsForEvent($this->xoctEvent, $this->xoctOpenCast->getPermissionAllowSetOwn());
		$invited_user_ids = array();
		foreach ($active_invitations as $inv) {
			$invited_user_ids[] = $inv->getUserId();
		}


		$available_user_ids = array_diff($course_members_user_ids, $invited_user_ids);
		$invited_users = array();
		$available_users = array();
		$owner = $this->xoctEvent->getOwner();
		foreach ($available_user_ids as $user_id) {
			if ($user_id == self::dic()->user()->getId()) {
				continue;
			}
			if ($owner && $user_id == $owner->getIliasUserId()) {
				continue;
			}
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

		usort($invited_users, ['xoctGUI', 'compareStdClassByName']);
		usort($available_users, ['xoctGUI', 'compareStdClassByName']);

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
		$parent = ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id']);
		$p = $parent->getMembersObject();

		return array_merge($p->getMembers(), $p->getTutors(), $p->getAdmins());
	}


	protected function create() {
		$obj = xoctInvitation::where(array(
			'event_identifier' => $this->xoctEvent->getIdentifier(),
			'user_id' => $_POST['id'],
		))->first();
		$new = false;
		if (! $obj instanceof xoctInvitation) {
			$obj = new xoctInvitation();
			$new = true;
		}
		$obj->setEventIdentifier($this->xoctEvent->getIdentifier());
		$obj->setUserId($_POST['id']);
		$obj->setOwnerId(self::dic()->user()->getId());
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
	}


	protected function confirmDelete() {
	}


	protected function delete() {
		$obj = xoctInvitation::where(array(
			'event_identifier' => $this->xoctEvent->getIdentifier(),
			'user_id' => $_POST['id'],
		))->first();
		if ($obj instanceof xoctInvitation) {
			$obj->delete();
		}
	}
}