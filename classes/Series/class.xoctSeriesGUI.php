<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequestPayload;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesMetadataRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesMetadataRequestPayload;
use srag\Plugins\Opencast\Model\Series\SeriesRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\UI\SeriesFormBuilder;
use ILIAS\DI\HTTPServices;

/**
 * Class xoctSeriesGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctSeriesGUI : ilObjOpenCastGUI
 */
class xoctSeriesGUI extends xoctGUI
{
    public const SERIES_ID = 'series_id';

    public const CMD_EDIT_GENERAL = 'editGeneral';
    public const CMD_EDIT = self::CMD_EDIT_GENERAL;
    public const CMD_EDIT_WORKFLOW_PARAMS = 'editWorkflowParameters';
    public const CMD_UPDATE_GENERAL = 'updateGeneral';
    public const CMD_UPDATE = self::CMD_UPDATE_GENERAL;
    public const CMD_UPDATE_WORKFLOW_PARAMS = 'updateWorkflowParameters';

    public const SUBTAB_GENERAL = 'general';
    public const SUBTAB_WORKFLOW_PARAMETERS = 'workflow_params';

    /**
     * @var ilObjOpenCastGUI
     */
    private $parent_gui;
    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var ilObjOpenCast
     */
    protected $object;
    /**
     * @var SeriesFormBuilder
     */
    private $seriesFormBuilder;
    /**
     * @var SeriesRepository
     */
    private $seriesRepository;
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $seriesWorkflowParameterRepository;
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ILIAS\DI\UIServices
     */
    private $ui;

    public function __construct(
        ilObjOpenCastGUI $parent_gui,
        ilObjOpenCast $object,
        SeriesFormBuilder $seriesFormBuilder,
        SeriesRepository $seriesRepository,
        SeriesWorkflowParameterRepository $seriesWorkflowParameterRepository,
        WorkflowParameterRepository $workflowParameterRepository
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->ui = $DIC->ui();
        $this->objectSettings = ObjectSettings::find($object->getId());
        $this->parent_gui = $parent_gui;
        $this->object = $object;
        $this->seriesFormBuilder = $seriesFormBuilder;
        $this->seriesRepository = $seriesRepository;
        $this->seriesWorkflowParameterRepository = $seriesWorkflowParameterRepository;
        $this->workflowParameterRepository = $workflowParameterRepository;
    }

    public function executeCommand(): void
    {
        if (!ilObjOpenCastAccess::hasWriteAccess()) {
            $this->ctrl->redirectByClass('xoctEventGUI');
        }
        $this->tabs->activateTab(ilObjOpenCastGUI::TAB_SETTINGS);
        $this->setSubTabs();
        parent::executeCommand();
    }

    protected function setSubTabs(): void
    {
        if (PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
            $this->tabs->addSubTab(
                self::SUBTAB_GENERAL,
                $this->plugin->txt('subtab_' . self::SUBTAB_GENERAL),
                $this->ctrl->getLinkTarget($this, self::CMD_EDIT_GENERAL)
            );
            $this->tabs->addSubTab(
                self::SUBTAB_WORKFLOW_PARAMETERS,
                $this->plugin->txt('subtab_' . self::SUBTAB_WORKFLOW_PARAMETERS),
                $this->ctrl->getLinkTarget($this, self::CMD_EDIT_WORKFLOW_PARAMS)
            );
        }
    }

    protected function index(): void
    {
        $this->tabs->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
    }

    protected function edit(): void
    {
        $this->editGeneral();
    }

    protected function editGeneral(): void
    {
        $seriesIdentifier = $this->objectSettings->getSeriesIdentifier();
        if ($seriesIdentifier === null) {
            return;
        }

        $series = $this->seriesRepository->find($this->objectSettings->getSeriesIdentifier());

        $this->object->updateObjectFromSeries($series->getMetadata());
        $pre_form_data = $this->parent_gui->renderLinksListSection();
        if (!empty($pre_form_data)) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates'));
        }
        $this->tabs->activateSubTab(self::SUBTAB_GENERAL);
        $form = $this->seriesFormBuilder->update(
            $this->ctrl->getLinkTarget($this, self::CMD_UPDATE_GENERAL),
            $this->objectSettings,
            $series,
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        );
        $this->main_tpl->setContent($pre_form_data . $this->ui->renderer()->render($form));
    }

    protected function update(): void
    {
        $this->updateGeneral();
    }

    protected function updateGeneral(): void
    {
        $series = $this->seriesRepository->find($this->objectSettings->getSeriesIdentifier());
        $form = $this->seriesFormBuilder->update(
            $this->ctrl->getFormAction($this),
            $this->objectSettings,
            $series,
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        )->withRequest($this->http->request());

        $data = $form->getData();
        if (!$data) {
            $this->main_tpl->setContent($this->ui->renderer()->render($form));
            return;
        }

        /** @var ObjectSettings $objectSettings */
        $objectSettings = $data['settings']['object'];
        $objectSettings->setObjId($this->getObjId());
        $objectSettings->setSeriesIdentifier($this->objectSettings->getSeriesIdentifier());
        $objectSettings->update();

        $perm_tpl_id = $data['settings']['permission_template'] ?? null;

        $current_acls = $series->getAccessPolicies()->jsonSerialize();
        $current_acl_record = [];
        if (!empty($current_acls)) {
            $current_acl_record = array_map(function ($acl_entry) {
                return $acl_entry->jsonSerialize();
            }, $current_acls);
        }

        $series->setAccessPolicies(PermissionTemplate::removeAllTemplatesFromAcls($series->getAccessPolicies()));
        $default_perm_tpl = PermissionTemplate::where(['is_default' => 1])->first();

        if (empty($perm_tpl_id)) {
            $perm_tpl = $default_perm_tpl;
        } else {
            $perm_tpl = PermissionTemplate::find($perm_tpl_id) ?? $default_perm_tpl;
        }

        if ($perm_tpl instanceof PermissionTemplate) {
            $series->setAccessPolicies(
                $perm_tpl->addToAcls(
                    $series->getAccessPolicies(),
                    $objectSettings->getUseAnnotations()
                )
            );
        } else {
        }

        /** @var Metadata $metadata */
        $metadata = $data['metadata']['object'];
        $this->seriesRepository->updateMetadata(
            new UpdateSeriesMetadataRequest(
                $this->objectSettings->getSeriesIdentifier(),
                new UpdateSeriesMetadataRequestPayload($metadata)
            )
        );

        $new_acls = $series->getAccessPolicies()->jsonSerialize();
        $new_acl_record = [];
        if (!empty($new_acls)) {
            $new_acl_record = array_map(function ($acl_entry) {
                return $acl_entry->jsonSerialize();
            }, $new_acls);
        }
        if (json_encode($current_acl_record) !== json_encode($new_acl_record)) {
            $this->seriesRepository->updateACL(
                new UpdateSeriesACLRequest(
                    $this->objectSettings->getSeriesIdentifier(),
                    new UpdateSeriesACLRequestPayload($series->getAccessPolicies())
                )
            );
        }

        $this->object->updateObjectFromSeries($metadata);

        $this->objectSettings->updateAllDuplicates($metadata);
        $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('series_saved'), true);
        $this->ctrl->redirect($this, self::CMD_EDIT_GENERAL);
    }

    protected function editWorkflowParameters(): void
    {
        $this->seriesWorkflowParameterRepository->syncAvailableParameters($this->getObjId());
        $pre_form_data = $this->parent_gui->renderLinksListSection();
        if (!empty($pre_form_data)) {
            $this->main_tpl->setOnScreenMessage('info', $this->plugin->txt('series_has_duplicates'));
        }
        $this->tabs->activateSubTab(self::SUBTAB_WORKFLOW_PARAMETERS);

        $xoctSeriesFormGUI = new xoctSeriesWorkflowParameterTableGUI(
            $this,
            self::CMD_EDIT_WORKFLOW_PARAMS,
            $this->workflowParameterRepository
        );
        $this->main_tpl->setContent($pre_form_data . $xoctSeriesFormGUI->getHTML());
    }

    protected function updateWorkflowParameters(): void
    {
        $post_workflow_params = $this->http->request()->getParsedBody()['workflow_parameter'] ?? [];

        foreach (
            $post_workflow_params as $param_id => $value
        ) {
            $value_admin = $value['value_admin'] ?? null;
            $value_member = $value['value_member'] ?? null;
            if (
                in_array($value_member, WorkflowParameter::$possible_values, true)
                && in_array($value_admin, WorkflowParameter::$possible_values, true)
            ) {
                SeriesWorkflowParameterRepository::getByObjAndParamId(
                    $this->getObjId(),
                    $param_id
                )->setDefaultValueAdmin($value_admin)->setValueMember($value_member)->update();
            }
        }
        $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_EDIT_WORKFLOW_PARAMS);
    }

    protected function cancel(): void
    {
        $this->ctrl->redirectByClass('xoctEventGUI', xoctEventGUI::CMD_STANDARD);
    }

    public function getObjId(): int
    {
        return $this->objectSettings->getObjId();
    }

    public function getObject(): ilObjOpenCast
    {
        return $this->object;
    }

    protected function add(): void
    {
    }

    protected function create(): void
    {
    }

    protected function confirmDelete(): void
    {
    }

    protected function delete(): void
    {
    }
}
