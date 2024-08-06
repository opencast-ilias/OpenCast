<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\ACL\ACLUtils;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequest;
use srag\Plugins\Opencast\Model\Event\Request\UpdateEventRequestPayload;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * Class xoctChangeOwnerGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctChangeOwnerGUI: ilObjOpenCastGUI
 */
class xoctChangeOwnerGUI extends xoctGUI
{
    protected Event $event;
    /**
     * @readonly
     */
    private EventRepository $event_repository;
    /**
     * @var \ilObjUser
     */
    private $user;

    public function __construct(protected ObjectSettings $objectSettings, EventRepository $event_repository, private ACLUtils $ACLUtils)
    {
        global $DIC;
        parent::__construct();
        $tabs = $DIC->tabs();
        $ctrl = $DIC->ctrl();
        $main_tpl = $DIC->ui()->mainTemplate();
        $this->user = $DIC->user();
        $this->event = $event_repository->find($this->http->request()->getQueryParams()[xoctEventGUI::IDENTIFIER]);
        $this->event_repository = $event_repository;
        $tabs->clearTargets();
        $tabs->setBackTarget(
            $this->plugin->txt('tab_back'),
            $ctrl->getLinkTargetByClass(xoctEventGUI::class)
        );

        new WaitOverlay($this->main_tpl); // TODO check if needed

        $main_tpl->addCss($this->plugin->getStyleSheetLocation('default/change_owner.css'));
        $main_tpl->addJavaScript($this->plugin->getStyleSheetLocation('default/change_owner.js'));
        $ctrl->saveParameter($this, xoctEventGUI::IDENTIFIER);
    }

    protected function index(): void
    {
        $xoctUser = xoctUser::getInstance($this->user);
        if (!ilObjOpenCastAccess::checkAction(
            ilObjOpenCastAccess::ACTION_SHARE_EVENT,
            $this->event,
            $xoctUser,
            $this->objectSettings
        )) {
            $this->main_tpl->setOnScreenMessage('failure', 'Access denied', true);
            $this->ctrl->redirectByClass(xoctEventGUI::class);
        }
        $temp = $this->plugin->getTemplate('default/tpl.change_owner.html', false, false);
        $temp->setVariable('PREVIEW', $this->event->publications()->getThumbnailUrl());
        $temp->setVariable('VIDEO_TITLE', $this->event->getTitle());
        $temp->setVariable('L_FILTER', $this->plugin->txt('groups_participants_filter'));
        $temp->setVariable(
            'PH_FILTER',
            $this->plugin->txt('groups_participants_filter_placeholder')
        );
        $temp->setVariable('HEADER_OWNER', $this->plugin->txt('current_owner_header'));
        $temp->setVariable(
            'HEADER_PARTICIPANTS_AVAILABLE',
            $this->plugin->txt('groups_available_participants_header')
        );
        $temp->setVariable('BASE_URL', ($this->ctrl->getLinkTarget($this, '', '', true)));
        $temp->setVariable(
            'LANGUAGE',
            json_encode([
                'none_available' => $this->plugin->txt('invitations_none_available'),
                'only_one_owner' => $this->plugin->txt('owner_only_one_owner')
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
        $this->sendJsonResponse(json_encode($data));
    }

    /**
     *
     */
    protected function add(): void
    {
    }

    /**
     *
     */
    public function getAll(): void
    {
        $owner = $this->ACLUtils->getOwnerOfEvent($this->event);
        $owner_data = $owner instanceof xoctUser ? [
            'id' => $owner->getIliasUserId(),
            'name' => $owner->getNamePresentation()
        ] : [];

        $available_user_ids = $this->getCourseMembers();
        $available_users = [];
        foreach ($available_user_ids as $user_id) {
            $user_id = (int) $user_id;
            if ($owner && $user_id === $owner->getIliasUserId()) {
                continue;
            }
            $user = new stdClass();
            $xoctUser = xoctUser::getInstance($user_id);
            $user->id = $user_id;
            $user->name = $xoctUser->getNamePresentation();
            $available_users[] = $user;
        }

        usort($available_users, ['xoctGUI', 'compareStdClassByName']);

        $arr = [
            'owner' => $owner_data,
            'available' => $available_users,
        ];

        $this->outJson($arr);
    }

    protected function getCourseMembers(): array
    {
        global $DIC;
        $ref_id = (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);

        $parent = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
        if ($parent === null) {
            return [];
        }
        $p = $parent->getMembersObject();

        return array_merge($p->getMembers(), $p->getTutors(), $p->getAdmins());
    }

    protected function setOwner(): void
    {
        $user_id = (int) $this->http->request()->getQueryParams()['user_id'];
        $this->event->setAcl(
            $this->ACLUtils->changeOwner(
                $this->event->getAcl(),
                xoctUser::getInstance($user_id)
            )
        );
        $this->event_repository->updateACL(
            new UpdateEventRequest(
                $this->event->getIdentifier(),
                new UpdateEventRequestPayload(null, $this->event->getAcl())
            )
        );
    }

    /**
     * async function
     */
    protected function removeOwner()
    {
        $this->event->setAcl($this->ACLUtils->removeOwnerFromACL($this->event->getAcl()));
        $this->event_repository->updateACL(
            new UpdateEventRequest(
                $this->event->getIdentifier(),
                new UpdateEventRequestPayload(null, $this->event->getAcl())
            )
        );
    }

    /**
     *
     */
    protected function create(): void
    {
    }

    /**
     *
     */
    protected function edit(): void
    {
    }

    /**
     *
     */
    protected function update(): void
    {
    }

    /**
     *
     */
    protected function confirmDelete(): void
    {
    }

    /**
     *
     */
    protected function delete(): void
    {
    }
}
