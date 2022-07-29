<?php

use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\DI\OpencastDIC;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class xoctMainGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy xoctMainGUI : ilOpenCastConfigGUI
 */
class xoctMainGUI extends xoctGUI
{
    public const TAB_SETTINGS = 'settings';
    public const TAB_PUBLICATION_USAGE = 'publication_usage';
    public const TAB_VIDEO_PORTAL = 'video_portal';
    public const TAB_EXPORT = 'export';
    public const TAB_REPORTS = 'reports';
    public const TAB_WORKFLOW_PARAMETERS = 'workflow_params';
    public const TAB_WORKFLOWS = 'workflows';
    public const TAB_METADATA = 'metadata';

    public const SUBTAB_API = 'api';
    public const SUBTAB_TOU = 'terms_of_use';
    public const SUBTAB_EVENTS = 'events';
    public const SUBTAB_PLAYER = 'player';
    public const SUBTAB_GROUPS_ROLES = 'groups_roles';
    public const SUBTAB_SECURITY = 'security';
    public const SUBTAB_ADVANCED = 'advanced';


    /**
     * @throws DICException
     * @throws ilCtrlException
     */
    public function executeCommand()
    {
        global $DIC;
        $nextClass = self::dic()->ctrl()->getNextClass();

        self::dic()->tabs()->addTab(self::TAB_SETTINGS, self::plugin()->translate('tab_' . self::TAB_SETTINGS), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->tabs()->addTab(self::TAB_WORKFLOWS, self::plugin()->translate('tab_' . self::TAB_WORKFLOWS), self::dic()->ctrl()->getLinkTargetByClass(xoctWorkflowGUI::class));
        self::dic()->tabs()->addTab(self::TAB_WORKFLOW_PARAMETERS, self::plugin()->translate('tab_' . self::TAB_WORKFLOW_PARAMETERS), self::dic()->ctrl()->getLinkTargetByClass(xoctWorkflowParameterGUI::class));
        self::dic()->tabs()->addTab(self::TAB_PUBLICATION_USAGE, self::plugin()->translate('tab_'
            . self::TAB_PUBLICATION_USAGE), self::dic()->ctrl()->getLinkTarget(new xoctPublicationUsageGUI()));
        self::dic()->tabs()->addTab(self::TAB_METADATA, self::plugin()->translate('tab_' . self::TAB_METADATA), self::dic()->ctrl()->getLinkTarget(new xoctMetadataConfigRouterGUI()));
        self::dic()->tabs()->addTab(self::TAB_VIDEO_PORTAL, self::plugin()->translate('tab_' . self::TAB_VIDEO_PORTAL), self::dic()->ctrl()->getLinkTarget(new xoctPermissionTemplateGUI()));
        self::dic()->tabs()->addTab(self::TAB_EXPORT, self::plugin()->translate('tab_' . self::TAB_EXPORT), self::dic()->ctrl()->getLinkTarget(new xoctConfExportGUI()));
        self::dic()->tabs()->addTab(self::TAB_REPORTS, self::plugin()->translate('tab_' . self::TAB_REPORTS), self::dic()->ctrl()->getLinkTarget(new xoctReportOverviewGUI()));

        $opencast_dic = OpencastDIC::getInstance();
        switch ($nextClass) {
            case strtolower(xoctPublicationUsageGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_PUBLICATION_USAGE);
                $xoctPublicationUsageGUI = new xoctPublicationUsageGUI();
                self::dic()->ctrl()->forwardCommand($xoctPublicationUsageGUI);
                break;
            case strtolower(xoctPermissionTemplateGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_VIDEO_PORTAL);
                $xoctPermissionTemplateGUI = new xoctPermissionTemplateGUI();
                self::dic()->ctrl()->forwardCommand($xoctPermissionTemplateGUI);
                break;
            case strtolower(xoctConfExportGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_EXPORT);
                $xoctConfExportGUI = new xoctConfExportGUI();
                self::dic()->ctrl()->forwardCommand($xoctConfExportGUI);
                break;
            case strtolower(xoctReportOverviewGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_REPORTS);
                $xoctReportOverviewGUI = new xoctReportOverviewGUI();
                self::dic()->ctrl()->forwardCommand($xoctReportOverviewGUI);
                break;
            case strtolower(xoctWorkflowGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_WORKFLOWS);
                $xoctWorkflowGUI = new xoctWorkflowGUI($opencast_dic->workflow_repository());
                self::dic()->ctrl()->forwardCommand($xoctWorkflowGUI);
                break;
            case strtolower(xoctWorkflowParameterGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_WORKFLOW_PARAMETERS);
                $xoctWorkflowParameterGUI = new xoctWorkflowParameterGUI(
                    $opencast_dic->workflow_parameter_conf_repository(),
                    $opencast_dic->workflow_parameter_series_repository()
                );
                self::dic()->ctrl()->forwardCommand($xoctWorkflowParameterGUI);
                break;
            case strtolower(xoctMetadataConfigRouterGUI::class):
                self::dic()->tabs()->activateTab(self::TAB_METADATA);
                $xoctMetadataConfigGUI = new xoctMetadataConfigRouterGUI();
                self::dic()->ctrl()->forwardCommand($xoctMetadataConfigGUI);
                break;
            default:
                self::dic()->tabs()->activateTab(self::TAB_SETTINGS);
                $this->setSubTabs();
                $xoctConfGUI = new xoctConfGUI(
                    $DIC->ui()->renderer(),
                    $DIC->ctrl(),
                    $opencast_dic->paella_config_upload_handler(),
                    $opencast_dic->paella_config_form_builder()
                );
                self::dic()->ctrl()->forwardCommand($xoctConfGUI);
                break;
        }
    }

    protected function setSubTabs()
    {
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_API);
        self::dic()->tabs()->addSubTab(self::SUBTAB_API, self::plugin()->translate('subtab_' . self::SUBTAB_API), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_EVENTS);
        self::dic()->tabs()->addSubTab(self::SUBTAB_EVENTS, self::plugin()->translate('subtab_' . self::SUBTAB_EVENTS), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_PLAYER);
        self::dic()->tabs()->addSubTab(self::SUBTAB_PLAYER, self::plugin()->translate('subtab_' . self::SUBTAB_PLAYER), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class, 'player'));
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_TOU);
        self::dic()->tabs()->addSubTab(self::SUBTAB_TOU, self::plugin()->translate('eula'), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_GROUPS_ROLES);
        self::dic()->tabs()->addSubTab(self::SUBTAB_GROUPS_ROLES, self::plugin()->translate('subtab_' . self::SUBTAB_GROUPS_ROLES), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_SECURITY);
        self::dic()->tabs()->addSubTab(self::SUBTAB_SECURITY, self::plugin()->translate('subtab_' . self::SUBTAB_SECURITY), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->ctrl()->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_ADVANCED);
        self::dic()->tabs()->addSubTab(self::SUBTAB_ADVANCED, self::plugin()->translate('subtab_' . self::SUBTAB_ADVANCED), self::dic()->ctrl()->getLinkTargetByClass(xoctConfGUI::class));
        self::dic()->ctrl()->clearParametersByClass(xoctConfGUI::class);
    }


    protected function index()
    {
    }


    protected function add()
    {
    }


    protected function create()
    {
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
    }
}
