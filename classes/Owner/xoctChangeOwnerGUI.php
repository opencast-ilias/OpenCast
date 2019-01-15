<?php

/**
 * Class xoctChangeOwnerGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctChangeOwnerGUI extends xoctGUI {

    /**
     * @var xoctEvent
     */
    protected $xoctEvent;

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
        $this->xoctEvent = xoctEvent::find($_GET[xoctEventGUI::IDENTIFIER]);
        $this->tabs->clearTargets();


        $this->tabs->setBackTarget($this->pl->txt('tab_back'), $this->ctrl->getLinkTargetByClass('xoctEventGUI'));
        xoctWaiterGUI::loadLib();
        $this->tpl->addCss($this->pl->getStyleSheetLocation('default/invitations.css'));
        $this->tpl->addJavaScript($this->pl->getStyleSheetLocation('default/invitations.js'));
        $this->ctrl->saveParameter($this, xoctEventGUI::IDENTIFIER);
    }


    protected function index() {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $xoctUser = xoctUser::getInstance($ilUser);
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SHARE_EVENT, $this->xoctEvent, $xoctUser, $this->xoctOpenCast)) {
            ilUtil::sendFailure('Access denied', true);
            $this->ctrl->redirectByClass('xoctEventGUI');
        }
        $temp = $this->pl->getTemplate('default/tpl.invitations.html', false, false);
        $temp->setVariable('PREVIEW', $this->xoctEvent->getThumbnailUrl());
        $temp->setVariable('VIDEO_TITLE', $this->xoctEvent->getTitle());
        $temp->setVariable('L_FILTER', $this->pl->txt('groups_participants_filter'));
        $temp->setVariable('PH_FILTER', $this->pl->txt('groups_participants_filter_placeholder'));
        $temp->setVariable('HEADER_PARTICIPANTS_AVAILABLE', $this->pl->txt('groups_available_participants_header'));
        $temp->setVariable('BASE_URL', ($this->ctrl->getLinkTarget($this, '', '', true)));
        $temp->setVariable('LANGUAGE', json_encode(array(
            'none_available' => $this->pl->txt('invitations_none_available')
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
        $owner = $this->xoctEvent->getOwner();
        $owner_data = $owner ? ['id' => $owner->getIliasUserId(), 'name' => $owner->getNamePresentation()] : [];



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

    protected function setOwner() {
        $user_id = $_GET['user_id'];
        $this->xoctEvent->setOwner(xoctUser::getInstance($user_id));
        $this->xoctEvent->update();
//        $this->outJson($this->xoctEvent->__asStdClass());
    }


    protected function create() {

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
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $obj = xoctInvitation::where(array(
            'event_identifier' => $this->xoctEvent->getIdentifier(),
            'user_id' => $_POST['id'],
//			'owner_id' => $ilUser->getId()
        ))->first();
        if ($obj instanceof xoctInvitation) {
            $obj->delete();
        }
    }
}