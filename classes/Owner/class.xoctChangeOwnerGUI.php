<?php

use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventACLRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventACLRequestPayload;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Class xoctChangeOwnerGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctChangeOwnerGUI: ilObjOpenCastGUI
 */
class xoctChangeOwnerGUI extends xoctGUI
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
     * @var EventRepository
     */
    private $event_repository;

    public function __construct(ObjectSettings $objectSettings, EventRepository $event_repository, ACLUtils $ACLUtils)
    {
        $this->objectSettings = $objectSettings;
        $this->event = $event_repository->find($_GET[xoctEventGUI::IDENTIFIER]);
        $this->ACLUtils = $ACLUtils;
        $this->event_repository = $event_repository;
        self::dic()->tabs()->clearTargets();
        self::dic()->tabs()->setBackTarget(self::plugin()->getPluginObject()->txt('tab_back'), self::dic()->ctrl()->getLinkTargetByClass(xoctEventGUI::class));
        xoctWaiterGUI::loadLib();
        self::dic()->ui()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getStyleSheetLocation('default/change_owner.css'));
        self::dic()->ui()->mainTemplate()->addJavaScript(self::plugin()->getPluginObject()->getStyleSheetLocation('default/change_owner.js'));
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
        if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SHARE_EVENT, $this->event, $xoctUser, $this->objectSettings)) {
            ilUtil::sendFailure('Access denied', true);
            self::dic()->ctrl()->redirectByClass(xoctEventGUI::class);
        }
        $temp = self::plugin()->getPluginObject()->getTemplate('default/tpl.change_owner.html', false, false);
        $temp->setVariable('PREVIEW', $this->event->publications()->getThumbnailUrl());
        $temp->setVariable('VIDEO_TITLE', $this->event->getTitle());
        $temp->setVariable('L_FILTER', self::plugin()->getPluginObject()->txt('groups_participants_filter'));
        $temp->setVariable('PH_FILTER', self::plugin()->getPluginObject()->txt('groups_participants_filter_placeholder'));
        $temp->setVariable('HEADER_OWNER', self::plugin()->getPluginObject()->txt('current_owner_header'));
        $temp->setVariable('HEADER_PARTICIPANTS_AVAILABLE', self::plugin()->getPluginObject()->txt('groups_available_participants_header'));
        $temp->setVariable('BASE_URL', (self::dic()->ctrl()->getLinkTarget($this, '', '', true)));
        $temp->setVariable('LANGUAGE', json_encode(array(
            'none_available' => self::plugin()->getPluginObject()->txt('invitations_none_available'),
            'only_one_owner' => self::plugin()->getPluginObject()->txt('owner_only_one_owner')
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


    /**
     *
     */
    protected function add()
    {
    }


    /**
     *
     */
    public function getAll()
    {
        $owner = $this->ACLUtils->getOwner($this->event);
        $owner_data = $owner ? ['id' => $owner->getIliasUserId(), 'name' => $owner->getNamePresentation()] : [];

        $available_user_ids = $this->getCourseMembers();
        $available_users = [];
        foreach ($available_user_ids as $user_id) {
            if ($owner && $user_id == $owner->getIliasUserId()) {
                continue;
            }
            $user = new stdClass();
            $xoctUser = xoctUser::getInstance($user_id);
            $user->id = $user_id;
            $user->name = $xoctUser->getNamePresentation();
            $available_users[] = $user;
        }

        usort($available_users, ['xoctGUI', 'compareStdClassByName']);

        $arr = array(
            'owner' => $owner_data,
            'available' => $available_users,
        );

        $this->outJson($arr);
    }


    protected function getCourseMembers(): array
    {
        $parent = ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id']);
        $p = $parent->getMembersObject();

        return array_merge($p->getMembers(), $p->getTutors(), $p->getAdmins());
    }

    /**
     * async function
     *
     * @throws xoctException
     */
    protected function setOwner()
    {
        $user_id = $_GET['user_id'];
        $this->event = $this->ACLUtils->setOwner(xoctUser::getInstance($user_id), $this->event);
        $this->event_repository->updateACL(new UpdateEventACLRequest(
            $this->event->getIdentifier(),
            new UpdateEventACLRequestPayload($this->event->getAcl())
        ));
    }

    /**
     * async function
     */
    protected function removeOwner()
    {
        $this->event = $this->ACLUtils->removeOwner($this->event);
        $this->event_repository->updateACL(new UpdateEventACLRequest(
            $this->event->getIdentifier(),
            new UpdateEventACLRequestPayload($this->event->getAcl())
        ));
    }


    /**
     *
     */
    protected function create()
    {
    }


    /**
     *
     */
    protected function edit()
    {
    }


    /**
     *
     */
    protected function update()
    {
    }


    /**
     *
     */
    protected function confirmDelete()
    {
    }


    /**
     *
     */
    protected function delete()
    {
    }
}