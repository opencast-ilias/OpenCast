<?php

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
 * @ilCtrl_IsCalledBy xoctGrantPermissionGUI: ilObjOpenCastGUI
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
     * @var ACLUtils
     */
    private $ACLUtils;
    /**
     * @var \ilObjUser
     */
    private $user;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;

    public function __construct(ObjectSettings $objectSettings, EventRepository $event_repository, ACLUtils $ACLUtils)
    {
        global $DIC;
        parent::__construct();
        $tabs = $DIC->tabs();
        $main_tpl = $DIC->ui()->mainTemplate();
        $this->user = $DIC->user();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->objectSettings = $objectSettings;
        $this->event = $event_repository->find($_GET[xoctEventGUI::IDENTIFIER]);
        $this->ACLUtils = $ACLUtils;
        $tabs->clearTargets();

        $tabs->setBackTarget($this->plugin->txt('tab_back'), $this->ctrl->getLinkTargetByClass(xoctEventGUI::class));
        xoctWaiterGUI::loadLib();
        $main_tpl->addCss($this->plugin->getStyleSheetLocation('default/invitations.css'));
        $main_tpl->addJavaScript($this->plugin->getStyleSheetLocation('default/invitations.js'));
        $this->ctrl->saveParameter($this, xoctEventGUI::IDENTIFIER);
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function index()
    {
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(
            ilObjOpenCastAccess::ACTION_SHARE_EVENT,
            $this->event,
            $xoctUser,
            $this->objectSettings
        )) {
            ilUtil::sendFailure('Access denied', true);
            $this->ctrl->redirectByClass(xoctEventGUI::class);
        }
        $temp = $this->plugin->getTemplate('default/tpl.invitations.html', false, false);
        $temp->setVariable('PREVIEW', $this->event->publications()->getThumbnailUrl());
        $temp->setVariable('VIDEO_TITLE', $this->event->getTitle());
        $temp->setVariable('L_FILTER', $this->plugin->txt('groups_participants_filter'));
        $temp->setVariable('PH_FILTER', $this->plugin->txt('groups_participants_filter_placeholder'));
        $temp->setVariable('HEADER_INVITAIONS', $this->plugin->txt('invitations_header'));
        $temp->setVariable(
            'HEADER_PARTICIPANTS_AVAILABLE',
            $this->plugin->txt('groups_available_participants_header')
        );
        $temp->setVariable('BASE_URL', ($this->ctrl->getLinkTarget($this, '', '', true)));
        $temp->setVariable(
            'LANGUAGE',
            json_encode([
                'none_available' => $this->plugin->txt('invitations_none_available'),
                'invite_all' => $this->plugin->txt('invitations_invite_all')
            ])
        );
        $this->main_tpl->setContent($temp->get());
    }

    /**
     * @param $data
     * @return never
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

    public function getAll(): void
    {
        /**
         * @var $xoctUser xoctUser
         */
        $xoctUsers = [];
        $course_members_user_ids = $this->getCourseMembers();
        foreach ($course_members_user_ids as $user_id) {
            $xoctUsers[$user_id] = xoctUser::getInstance(new ilObjUser($user_id));
        }
        $active_invitations = PermissionGrant::getActiveInvitationsForEvent(
            $this->event,
            $this->objectSettings->getPermissionAllowSetOwn()
        );
        $invited_user_ids = [];
        foreach ($active_invitations as $inv) {
            $invited_user_ids[] = $inv->getUserId();
        }

        $available_user_ids = array_diff($course_members_user_ids, $invited_user_ids);
        $invited_users = [];
        $available_users = [];
        $owner = $this->ACLUtils->getOwnerOfEvent($this->event);
        foreach ($available_user_ids as $user_id) {
            if ($user_id == $this->user->getId()) {
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

        $arr = [
            'invited' => $invited_users,
            'available' => $available_users,
        ];

        $this->outJson($arr);
    }

    protected function getCourseMembers(): array
    {
        $parent = ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id']);
        $p = $parent->getMembersObject();

        return array_merge($p->getMembers(), $p->getTutors(), $p->getAdmins());
    }

    protected function create()
    {
        $obj = PermissionGrant::where([
            'event_identifier' => $this->event->getIdentifier(),
            'user_id' => $_POST['id'],
        ])->first();
        $new = false;
        if (!$obj instanceof PermissionGrant) {
            $obj = new PermissionGrant();
            $new = true;
        }
        $obj->setEventIdentifier($this->event->getIdentifier());
        $obj->setUserId($_POST['id']);
        $obj->setOwnerId($this->user->getId());
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
            $obj = PermissionGrant::where([
                'event_identifier' => $this->event->getIdentifier(),
                'user_id' => $id,
            ])->first();
            $new = false;
            if (!$obj instanceof PermissionGrant) {
                $obj = new PermissionGrant();
                $new = true;
            }
            $obj->setEventIdentifier($this->event->getIdentifier());
            $obj->setUserId($id);
            $obj->setOwnerId($this->user->getId());
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
        $obj = PermissionGrant::where([
            'event_identifier' => $this->event->getIdentifier(),
            'user_id' => $_POST['id'],
        ])->first();
        if ($obj instanceof PermissionGrant) {
            $obj->delete();
        }
    }
}
