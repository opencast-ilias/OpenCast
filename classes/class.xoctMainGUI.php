<?php

declare(strict_types=1);

use srag\Plugins\Opencast\DI\OpencastDIC;

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

    public const SUBTAB_WORKFLOWS_SETTINGS = 'wf_settings';
    public const SUBTAB_WORKFLOWS_LIST = 'wf_list';
    /**
     * @var \ilTabsGUI
     */
    private $tabs;

    public const SUBTAB_PUBLICATION_USAGE = 'publication_usage';
    public const SUBTAB_PUBLICATION_SUB_USAGE = 'publication_sub_usage';
    public const SUBTAB_PUBLICATION_GROUPS = 'publication_groups';

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        global $DIC;
        $nextClass = $this->ctrl->getNextClass();

        $this->tabs->addTab(
            self::TAB_SETTINGS,
            $this->plugin->txt('tab_' . self::TAB_SETTINGS),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->tabs->addTab(
            self::TAB_WORKFLOWS,
            $this->plugin->txt('tab_' . self::TAB_WORKFLOWS),
            $this->ctrl->getLinkTargetByClass(xoctWorkflowGUI::class)
        );
        $this->tabs->addTab(
            self::TAB_WORKFLOW_PARAMETERS,
            $this->plugin->txt('tab_' . self::TAB_WORKFLOW_PARAMETERS),
            $this->ctrl->getLinkTargetByClass(xoctWorkflowParameterGUI::class)
        );
        $this->tabs->addTab(
            self::TAB_PUBLICATION_USAGE,
            $this->plugin->txt(
                'tab_'
                . self::TAB_PUBLICATION_USAGE
            ),
            $this->ctrl->getLinkTarget(new xoctPublicationUsageGUI())
        );
        $this->tabs->addTab(
            self::TAB_METADATA,
            $this->plugin->txt('tab_' . self::TAB_METADATA),
            $this->ctrl->getLinkTarget(new xoctMetadataConfigRouterGUI())
        );
        $this->tabs->addTab(
            self::TAB_VIDEO_PORTAL,
            $this->plugin->txt('tab_' . self::TAB_VIDEO_PORTAL),
            $this->ctrl->getLinkTarget(new xoctPermissionTemplateGUI())
        );
        $this->tabs->addTab(
            self::TAB_EXPORT,
            $this->plugin->txt('tab_' . self::TAB_EXPORT),
            $this->ctrl->getLinkTarget(new xoctConfExportGUI())
        );
        $this->tabs->addTab(
            self::TAB_REPORTS,
            $this->plugin->txt('tab_' . self::TAB_REPORTS),
            $this->ctrl->getLinkTarget(new xoctReportOverviewGUI())
        );

        $opencast_dic = OpencastDIC::getInstance();
        switch ($nextClass) {
            case strtolower(xoctPublicationUsageGUI::class):
                $this->tabs->activateTab(self::TAB_PUBLICATION_USAGE);
                $this->setPublicationSubTabs();
                $xoctPublicationUsageGUI = new xoctPublicationUsageGUI();
                $this->ctrl->forwardCommand($xoctPublicationUsageGUI);
                break;
            case strtolower(xoctPermissionTemplateGUI::class):
                $this->tabs->activateTab(self::TAB_VIDEO_PORTAL);
                $xoctPermissionTemplateGUI = new xoctPermissionTemplateGUI();
                $this->ctrl->forwardCommand($xoctPermissionTemplateGUI);
                break;
            case strtolower(xoctConfExportGUI::class):
                $this->tabs->activateTab(self::TAB_EXPORT);
                $xoctConfExportGUI = new xoctConfExportGUI();
                $this->ctrl->forwardCommand($xoctConfExportGUI);
                break;
            case strtolower(xoctReportOverviewGUI::class):
                $this->tabs->activateTab(self::TAB_REPORTS);
                $xoctReportOverviewGUI = new xoctReportOverviewGUI();
                $this->ctrl->forwardCommand($xoctReportOverviewGUI);
                break;
            case strtolower(xoctWorkflowGUI::class):
                $this->tabs->activateTab(self::TAB_WORKFLOWS);
                $this->setWorkflowsSubTabs();
                $xoctWorkflowGUI = new xoctWorkflowGUI($opencast_dic->workflow_repository());
                $this->ctrl->forwardCommand($xoctWorkflowGUI);
                break;
            case strtolower(xoctWorkflowParameterGUI::class):
                $this->tabs->activateTab(self::TAB_WORKFLOW_PARAMETERS);
                $xoctWorkflowParameterGUI = new xoctWorkflowParameterGUI(
                    $opencast_dic->workflow_parameter_conf_repository(),
                    $opencast_dic->workflow_parameter_series_repository()
                );
                $this->ctrl->forwardCommand($xoctWorkflowParameterGUI);
                break;
            case strtolower(xoctMetadataConfigRouterGUI::class):
                $this->tabs->activateTab(self::TAB_METADATA);
                $xoctMetadataConfigGUI = new xoctMetadataConfigRouterGUI();
                $this->ctrl->forwardCommand($xoctMetadataConfigGUI);
                break;
            default:
                $this->tabs->activateTab(self::TAB_SETTINGS);
                $this->setSubTabs();
                $xoctConfGUI = new xoctConfGUI(
                    $DIC->ui()->renderer(),
                    $opencast_dic->paella_config_upload_handler(),
                    $opencast_dic->paella_config_form_builder()
                );
                $this->ctrl->forwardCommand($xoctConfGUI);
                break;
        }
    }

    protected function setSubTabs()
    {
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_API);
        $this->tabs->addSubTab(
            self::SUBTAB_API,
            $this->plugin->txt('subtab_' . self::SUBTAB_API),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_EVENTS);
        $this->tabs->addSubTab(
            self::SUBTAB_EVENTS,
            $this->plugin->txt('subtab_' . self::SUBTAB_EVENTS),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_PLAYER);
        $this->tabs->addSubTab(
            self::SUBTAB_PLAYER,
            $this->plugin->txt('subtab_' . self::SUBTAB_PLAYER),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class, 'player')
        );
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_TOU);
        $this->tabs->addSubTab(
            self::SUBTAB_TOU,
            $this->plugin->txt('eula'),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_GROUPS_ROLES);
        $this->tabs->addSubTab(
            self::SUBTAB_GROUPS_ROLES,
            $this->plugin->txt('subtab_' . self::SUBTAB_GROUPS_ROLES),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_SECURITY);
        $this->tabs->addSubTab(
            self::SUBTAB_SECURITY,
            $this->plugin->txt('subtab_' . self::SUBTAB_SECURITY),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_ADVANCED);
        $this->tabs->addSubTab(
            self::SUBTAB_ADVANCED,
            $this->plugin->txt('subtab_' . self::SUBTAB_ADVANCED),
            $this->ctrl->getLinkTargetByClass(xoctConfGUI::class)
        );
        $this->ctrl->clearParametersByClass(xoctConfGUI::class);
    }

    protected function setWorkflowsSubTabs()
    {
        $this->ctrl->setParameterByClass(xoctWorkflowGUI::class, 'wf_subtab_active', self::SUBTAB_WORKFLOWS_SETTINGS);
        $this->tabs->addSubTab(
            self::SUBTAB_WORKFLOWS_SETTINGS,
            $this->plugin->txt('subtab_' . self::SUBTAB_WORKFLOWS_SETTINGS),
            $this->ctrl->getLinkTargetByClass(xoctWorkflowGUI::class)
        );
        $this->ctrl->setParameterByClass(xoctWorkflowGUI::class, 'wf_subtab_active', self::SUBTAB_WORKFLOWS_LIST);
        $this->tabs->addSubTab(
            self::SUBTAB_WORKFLOWS_LIST,
            $this->plugin->txt('subtab_' . self::SUBTAB_WORKFLOWS_LIST),
            $this->ctrl->getLinkTargetByClass(xoctWorkflowGUI::class)
        );
    }

    protected function setPublicationSubTabs()
    {
        $this->ctrl->setParameterByClass(
            xoctPublicationUsageGUI::class,
            'pub_subtab_active',
            self::SUBTAB_PUBLICATION_USAGE
        );
        $this->tabs->addSubTab(
            self::SUBTAB_PUBLICATION_USAGE,
            $this->plugin->txt('subtab_' . self::SUBTAB_PUBLICATION_USAGE),
            $this->ctrl->getLinkTargetByClass(xoctPublicationUsageGUI::class)
        );
        $this->ctrl->setParameterByClass(
            xoctPublicationUsageGUI::class,
            'pub_subtab_active',
            self::SUBTAB_PUBLICATION_SUB_USAGE
        );
        $this->tabs->addSubTab(
            self::SUBTAB_PUBLICATION_SUB_USAGE,
            $this->plugin->txt('subtab_' . self::SUBTAB_PUBLICATION_SUB_USAGE),
            $this->ctrl->getLinkTargetByClass(xoctPublicationUsageGUI::class)
        );
        $this->ctrl->setParameterByClass(
            xoctPublicationUsageGUI::class,
            'pub_subtab_active',
            self::SUBTAB_PUBLICATION_GROUPS
        );
        $this->tabs->addSubTab(
            self::SUBTAB_PUBLICATION_GROUPS,
            $this->plugin->txt('subtab_' . self::SUBTAB_PUBLICATION_GROUPS),
            $this->ctrl->getLinkTargetByClass(xoctPublicationUsageGUI::class)
        );
        $this->ctrl->clearParametersByClass(xoctPublicationUsageGUI::class);
    }

    protected function index(): void
    {
    }

    protected function add(): void
    {
    }

    protected function create(): void
    {
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
    }
}
