<?php

use srag\DIC\OpencastObject\Exception\DICException;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * Class xoctGrantPermissionGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctGrantPermissionGUI: ilObjOpencastObjectGUI
 */
class xoctGrantPermissionGUI extends xoctGUI
{

    /**
     * @var Event
     */
    protected $event;
    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var EventRepository
     */
    private $event_repository;
    /**
     * @var ACLUtils
     */
    private $ACLUtils;

    public function __construct(ObjectSettings $objectSettings, EventRepository $event_repository, ACLUtils $ACLUtils)
    {
        $this->objectSettings = $objectSettings;
        $this->event_repository = $event_repository;
        $this->event = $event_repository->find($_GET[xoctEventGUI::IDENTIFIER]);
        $this->ACLUtils = $ACLUtils;
        self::dic()->tabs()->clearTargets();


        self::dic()->tabs()->setBackTarget(self::plugin()->translate('tab_back'), self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class));
        xoctWaiterGUI::loadLib();
        self::dic()->ui()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getStyleSheetLocation('default/invitations.css'));
        self::dic()->ui()->mainTemplate()->addJavaScript(self::plugin()->getPluginObject()->getStyleSheetLocation('default/invitations.js'));
        self::dic()->ctrl()->saveParameter($this, xoctEventGUI::IDENTIFIER);
    }


    /**
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function index()
    {
        $xoctUser = xoctUser::getInstance(self::dic()->user());
        if (!ilObjOpencastObjectAccess::checkAction(ilObjOpencastObjectAccess::ACTION_SHARE_EVENT, $this->event, $xoctUser, $this->objectSettings)) {
            ilUtil::sendFailure('Access denied', true);
            self::dic()->ctrl()->redirectByClass(xoctEventGUI::class);
        }
        $temp = self::plugin()->getPluginObject()->getTemplate('default/tpl.invitations.html', false, false);
        $temp->setVariable('PREVIEW', $this->event->publications()->getThumbnailUrl());
        $temp->setVariable('VIDEO_TITLE', $this->event->getTitle());
        $temp->setVariable('L_FILTER', self::plugin()->translate('groups_participants_filter'));
        $temp->setVariable('PH_FILTER', self::plugin()->translate('groups_participants_filter_placeholder'));
        $temp->setVariable('HEADER_INVITAIONS', self::plugin()->translate('invitations_header'));
        $temp->setVariable('HEADER_PARTICIPANTS_AVAILABLE', self::plugin()->translate('groups_available_participants_header'));
        $temp->setVariable('BASE_URL', (self::dic()->ctrl()->getLinkTarget($this, '', '', true)));
        $temp->setVariable('LANGUAGE', json_encode(array(
            'none_available' => self::plugin()->translate('invitations_none_available'),
            'invite_all' => self::plugin()->translate('invitations_invite_all')
        )));
        self::dic()->ui()->mainTemplate()->setContent($temp->get());
    }


    /**
     * @param $data
     */
    protected function outJson($data)
    {
        header('Content-type: application/json');
        echo json_encode($data);
        exit;
    }


    protected function add()
    {
    }


    public function getAll()
    {
        /**
         * @var $xoctUser xoctUser
         */
        $xoctUsers = array();
        $course_members_user_ids = $this->getCourseMembers();
        foreach ($course_members_user_ids as $user_id) {
            $xoctUsers[$user_id] = xoctUser::getInstance(new ilObjUser($user_id));
        }
        $active_invitations = PermissionGrant::getActiveInvitationsForEvent($this->event, $this->objectSettings->getPermissionAllowSetOwn());
        $invited_user_ids = array();
        foreach ($active_invitations as $inv) {
            $invited_user_ids[] = $inv->getUserId();
        }


        $available_user_ids = array_diff($course_members_user_ids, $invited_user_ids);
        $invited_users = array();
        $available_users = array();
        $owner = $this->ACLUtils->getOwnerOfEvent($this->event);
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
    protected function getCourseMembers()
    {
        $parent = ilObjOpencastObject::_getParentCourseOrGroup($_GET['ref_id']);
        $p = $parent->getMembersObject();

        return array_merge($p->getMembers(), $p->getTutors(), $p->getAdmins());
    }


    protected function create()
    {
        $obj = PermissionGrant::where(array(
            'event_identifier' => $this->event->getIdentifier(),
            'user_id' => $_POST['id'],
        ))->first();
        $new = false;
        if (!$obj instanceof PermissionGrant) {
            $obj = new PermissionGrant();
            $new = true;
        }
        $obj->setEventIdentifier($this->event->getIdentifier());
        $obj->setUserId($_POST['id']);
        $obj->setOwnerId(self::dic()->user()->getId());
        if ($new) {
            $obj->create();
        } else {
            $obj->update();
        }
        $this->outJson($obj->__asStdClass());
    }


    /**
     *
     */
    protected function createMultiple()
    {
        $objects = [];
        foreach ($_POST['ids'] as $id) {
            $obj = PermissionGrant::where(array(
                'event_identifier' => $this->event->getIdentifier(),
                'user_id' => $id,
            ))->first();
            $new = false;
            if (!$obj instanceof PermissionGrant) {
                $obj = new PermissionGrant();
                $new = true;
            }
            $obj->setEventIdentifier($this->event->getIdentifier());
            $obj->setUserId($id);
            $obj->setOwnerId(self::dic()->user()->getId());
            if ($new) {
                $obj->create();
            } else {
                $obj->update();
            }
            $objects[] = $obj->__asStdClass();
        }
        $this->outJson(json_encode($objects));
    }


    protected function edit()
    {
    }


    protected function update()
    {
    }


    protected function confirmDelete()
    {
    }


    protected function delete()
    {
        $obj = PermissionGrant::where(array(
            'event_identifier' => $this->event->getIdentifier(),
            'user_id' => $_POST['id'],
        ))->first();
        if ($obj instanceof PermissionGrant) {
            $obj->delete();
        }
    }
}
