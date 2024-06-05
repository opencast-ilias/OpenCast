<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroupParticipant;

/**
 * Class xoctPermissionGroupParticipantGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @ilCtrl_IsCalledBy xoctPermissionGroupParticipantGUI:ilObjOpenCastGUI
 */
class xoctPermissionGroupParticipantGUI extends xoctGUI
{
    /**
     * @var ObjectSettings
     */
    public $objectSettings;
    /**
     * @var array
     */
    protected static $admin_commands = [
        self::CMD_CREATE,
        self::CMD_DELETE
    ];

    public function __construct(?ObjectSettings $objectSettings = null)
    {
        global $DIC;
        parent::__construct();
        $tabs = $DIC->tabs();
        $main_tpl = $DIC->ui()->mainTemplate();
        $this->objectSettings = $objectSettings instanceof ObjectSettings ? $objectSettings : new ObjectSettings();
        $tabs->setTabActive(ilObjOpenCastGUI::TAB_GROUPS);

        new WaitOverlay($main_tpl); // TODO check if needed

        $main_tpl->addJavaScript(
            $this->plugin->getStyleSheetLocation('default/group_participants.js')
        );
    }

    /**
     * @param string $cmd
     */
    protected function performCommand(string $cmd): void
    {
        if (in_array($cmd, self::$admin_commands)) {
            $access = ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_MANAGE_IVT_GROUPS);
        } else {
            $access = ilObjOpenCastAccess::hasPermission('read');
        }
        if (!$access) {
            $this->main_tpl->setOnScreenMessage('failure', 'No access.');
            $this->ctrl->redirectByClass('xoctEventGUI');
        }
        parent::performCommand($cmd);
    }

    /**
     * @param $data
     * @return never
     */
    protected function outJson($data): void
    {
        $this->sendJsonResponse(json_encode($data));
    }

    protected function index(): void
    {
    }

    protected function getAvailable(): void
    {
        $data = [];
        /**
         * @var $xoctGroupParticipant PermissionGroupParticipant
         */
        foreach (
            PermissionGroupParticipant::getAvailable(
                (int) $this->http->request()->getQueryParams()['ref_id'],
                (int) $this->http->request()->getQueryParams()['group_id']
            ) as $xoctGroupParticipant
        ) {
            $stdClass = $xoctGroupParticipant->asStdClass();
            $stdClass->name = $xoctGroupParticipant->getXoctUser()->getNamePresentation(
                ilObjOpenCastAccess::hasWriteAccess()
            );
            $data[] = $stdClass;
        }

        usort($data, ['xoctGUI', 'compareStdClassByName']);

        $this->outJson($data);
    }

    protected function getPerGroup(): void
    {
        $data = [];
        $group_id = $this->http->request()->getQueryParams()['group_id'];
        if (!$group_id) {
            $this->outJson(null);
        }
        /**
         * @var $xoctGroupParticipant PermissionGroupParticipant
         */
        foreach (PermissionGroupParticipant::where(['group_id' => $group_id])->get() as $xoctGroupParticipant) {
            $stdClass = $xoctGroupParticipant->asStdClass();
            $stdClass->name = $xoctGroupParticipant->getXoctUser()->getNamePresentation();
            $data[] = $stdClass;
        }

        usort($data, ['xoctGUI', 'compareStdClassByName']);

        $this->outJson($data);
    }

    protected function add(): void
    {
    }

    protected function create(): void
    {
        if (
            !$this->http->request()->getParsedBody()['user_id']
            || !$this->http->request()->getParsedBody()['group_id']
        ) {
            $this->outJson(false);
        }
        $group_participant = new PermissionGroupParticipant();
        $group_participant->setUserId((int) $this->http->request()->getParsedBody()['user_id']);
        $group_participant->setGroupId((int) $this->http->request()->getParsedBody()['group_id']);
        $group_participant->create();
        $this->outJson(true);
    }

    protected function edit(): void
    {
    }

    protected function update(): void
    {
    }

    protected function confirmDelete(): void
    {
    }

    protected function delete(): void
    {
        if (!$this->http->request()->getParsedBody()['id'] || !$this->http->request()->getParsedBody()['group_id']) {
            $this->outJson(false);
        }
        $o = PermissionGroupParticipant::where(['user_id' => $this->http->request()->getParsedBody()['id'], 'group_id' => $this->http->request()->getParsedBody()['group_id']])->first();
        $o->delete();
        $this->outJson(true);
    }
}
