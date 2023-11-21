<?php

use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroupParticipant;

/**
 * Class xoctPermissionGroupGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPermissionGroupGUI: ilObjOpenCastGUI
 */
class xoctPermissionGroupGUI extends xoctGUI
{
    /**
     * @var array
     */
    protected static $admin_commands = [
        self::CMD_CREATE,
        self::CMD_DELETE
    ];
    /**
     * @var ObjectSettings
     */
    private $objectSettings;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;

    public function __construct(?ObjectSettings $objectSettings = null)
    {
        global $DIC;
        parent::__construct();
        $tabs = $DIC->tabs();
        $main_tpl = $DIC->ui()->mainTemplate();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        if ($objectSettings instanceof ObjectSettings) {
            $this->objectSettings = $objectSettings;
        } else {
            $this->objectSettings = new ObjectSettings();
        }
        $tabs->setTabActive(ilObjOpenCastGUI::TAB_GROUPS);

        new WaitOverlay($this->main_tpl); // TODO check if needed

        $main_tpl->addCss($this->plugin->getStyleSheetLocation('default/groups.css'));
        $main_tpl->addJavaScript($this->plugin->getStyleSheetLocation('default/groups.js'));
    }

    /**
     * @param $cmd
     */
    protected function performCommand($cmd)
    {
        if (in_array($cmd, self::$admin_commands)) {
            $access = ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_MANAGE_IVT_GROUPS);
        } else {
            $access = ilObjOpenCastAccess::hasPermission('read');
        }
        if (!$access) {
            ilUtil::sendFailure('No access.');
            $this->ctrl->redirectByClass('xoctEventGUI');
        }
        parent::performCommand($cmd);
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function index()
    {
        $temp = $this->plugin->getTemplate('default/tpl.groups.html', false, false);
        $temp->setVariable(
            'IS_ADMIN',
            (int) ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_MANAGE_IVT_GROUPS)
        );
        $temp->setVariable('HEADER_GROUPS', $this->plugin->txt('groups_header'));
        $temp->setVariable('HEADER_PARTICIPANTS', $this->plugin->txt('groups_participants_header'));
        $temp->setVariable(
            'HEADER_PARTICIPANTS_AVAILABLE',
            $this->plugin->txt('groups_available_participants_header')
        );
        $temp->setVariable('L_GROUP_NAME', $this->plugin->txt('groups_new'));
        $temp->setVariable('PH_GROUP_NAME', $this->plugin->txt('groups_new_placeholder'));
        $temp->setVariable('L_FILTER', $this->plugin->txt('groups_participants_filter'));
        $temp->setVariable('PH_FILTER', $this->plugin->txt('groups_participants_filter_placeholder'));
        $temp->setVariable('BUTTON_GROUP_NAME', $this->plugin->txt('groups_new_button'));
        $temp->setVariable('BASE_URL', ($this->ctrl->getLinkTarget($this, '', '', true)));
        $temp->setVariable(
            'GP_BASE_URL',
            ($this->ctrl->getLinkTarget(new xoctPermissionGroupParticipantGUI($this->objectSettings), '', '', true))
        );
        $temp->setVariable(
            'GROUP_LANGUAGE',
            json_encode([
                'no_title' => $this->plugin->txt('group_alert_no_title'),
                'delete_group' => $this->plugin->txt('group_alert_delete_group'),
                'none_available' => $this->plugin->txt('group_none_available')
            ])
        );
        $temp->setVariable(
            'PARTICIPANTS_LANGUAGE',
            json_encode([
                'delete_participant' => $this->plugin->txt('group_delete_participant'),
                'select_group' => $this->plugin->txt('group_select_group'),
                'none_available' => $this->plugin->txt('group_none_available'),
                'none_available_all' => $this->plugin->txt('group_none_available_all'),

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
        $arr = [];
        foreach (PermissionGroup::getAllForId($this->objectSettings->getObjId()) as $group) {
            $users = PermissionGroupParticipant::where(['group_id' => $group->getId()])->getArray(null, 'user_id');
            $stdClass = $group->__asStdClass();
            $stdClass->user_count = count($users);
            $stdClass->name = $stdClass->title;
            $stdClass->users = $users;
            $arr[] = $stdClass;
        }
        usort($arr, ['xoctGUI', 'compareStdClassByName']);
        $this->outJson($arr);
    }

    public function getParticipants(): void
    {
        $data = [];
        /**
         * @var $xoctGroupParticipant PermissionGroupParticipant
         */
        foreach (PermissionGroupParticipant::getAvailable($_GET['ref_id']) as $xoctGroupParticipant) {
            $data[] = [
                'user_id' => $xoctGroupParticipant->getUserId(),
                'name' => $xoctGroupParticipant->getXoctUser()->getNamePresentation(
                    ilObjOpenCastAccess::hasWriteAccess()
                )
            ];
        }

        $this->outJson($data);
    }

    /**
     * @return never
     */
    protected function create()
    {
        $obj = new PermissionGroup();
        $obj->setSerieId($this->objectSettings->getObjId());
        $obj->setTitle($_POST['title']);
        $obj->create();
        $json = $obj->__asStdClass();
        $json->users = [];
        $json->user_count = 0;
        $this->outJson($json);
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
        /**
         * @var $xoctIVTGroup PermissionGroup
         */
        $status = false;
        $xoctIVTGroup = PermissionGroup::find($_GET['id']);
        if ($xoctIVTGroup->getSerieId() == $this->objectSettings->getObjId()) {
            $xoctIVTGroup->delete();
            $status = true;
        }
        $this->outJson($status);
    }
}
