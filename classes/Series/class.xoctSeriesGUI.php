<?php

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
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ILIAS\DI\UIServices
     */
    private $ui;
    /**
     * @var \ILIAS\HTTP\Services
     */
    private $http;

    public function __construct(
        ilObjOpenCast $object,
        SeriesFormBuilder $seriesFormBuilder,
        SeriesRepository $seriesRepository,
        SeriesWorkflowParameterRepository $seriesWorkflowParameterRepository,
        WorkflowParameterRepository $workflowParameterRepository
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->ui = $DIC->ui();
        $this->http = $DIC->http();
        $this->objectSettings = ObjectSettings::find($object->getId());
        $this->object = $object;
        $this->seriesFormBuilder = $seriesFormBuilder;
        $this->seriesRepository = $seriesRepository;
        $this->seriesWorkflowParameterRepository = $seriesWorkflowParameterRepository;
        $this->workflowParameterRepository = $workflowParameterRepository;
    }

    /**
     *
     */
    public function executeCommand(): void
    {
        if (!ilObjOpenCastAccess::hasWriteAccess()) {
            $this->ctrl->redirectByClass('xoctEventGUI');
        }
        $this->tabs->activateTab(ilObjOpenCastGUI::TAB_SETTINGS);
        $this->setSubTabs();
        parent::executeCommand();
    }

    /**
     *
     */
    protected function setSubTabs()
    {
        if (PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
            $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
            $this->ctrl->setParameter($this, 'cmd', self::CMD_EDIT_GENERAL);
            $this->tabs->addSubTab(
                self::SUBTAB_GENERAL,
                $this->plugin->txt('subtab_' . self::SUBTAB_GENERAL),
                $this->ctrl->getLinkTarget($this)
            );
            $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_WORKFLOW_PARAMETERS);
            $this->ctrl->setParameter($this, 'cmd', self::CMD_EDIT_WORKFLOW_PARAMS);
            $this->tabs->addSubTab(
                self::SUBTAB_WORKFLOW_PARAMETERS,
                $this->plugin->txt('subtab_' . self::SUBTAB_WORKFLOW_PARAMETERS),
                $this->ctrl->getLinkTarget($this)
            );
        }
    }

    /**
     *
     */
    protected function index()
    {
        $this->tabs->activateTab(ilObjOpenCastGUI::TAB_EVENTS);
    }

    /**
     * @throws Exception
     */
    protected function edit()
    {
        $this->editGeneral();
    }

    /**
     * @throws Exception
     */
    protected function editGeneral()
    {
        $seriesIdentifier = $this->objectSettings->getSeriesIdentifier();
        if ($seriesIdentifier === null) {
            return;
        }

        $series = $this->seriesRepository->find($this->objectSettings->getSeriesIdentifier());

        $this->object->updateObjectFromSeries($series->getMetadata());
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo($this->plugin->txt('series_has_duplicates'));
        }
        $this->tabs->activateSubTab(self::SUBTAB_GENERAL);
        $form = $this->seriesFormBuilder->update(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE_GENERAL),
            $this->objectSettings,
            $series,
            ilObjOpenCastAccess::hasPermission('edit_videos')
        );
        $this->main_tpl->setContent($this->ui->renderer()->render($form));
    }

    /**
     * @throws xoctException
     */
    protected function update()
    {
        $this->updateGeneral();
    }

    /**
     * @throws DICException
     * @throws arException
     * @throws ilException
     * @throws xoctException
     */
    protected function updateGeneral()
    {
        $series = $this->seriesRepository->find($this->objectSettings->getSeriesIdentifier());
        $form = $this->seriesFormBuilder->update(
            $this->ctrl->getFormAction($this),
            $this->objectSettings,
            $series,
            ilObjOpenCastAccess::hasPermission('edit_videos')
        )
                                        ->withRequest($this->http->request());
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

        $perm_tpl_id = $data['settings']['permission_template'];
        $series->setAccessPolicies(PermissionTemplate::removeAllTemplatesFromAcls($series->getAccessPolicies()));
        if (empty($perm_tpl_id)) {
            $perm_tpl = PermissionTemplate::where(['is_default' => 1])->first();
        } else {
            /** @var PermissionTemplate $perm_tpl */
            $perm_tpl = PermissionTemplate::find($perm_tpl_id);
        }

        if ($perm_tpl instanceof PermissionTemplate) {
            $series->setAccessPolicies(
                $perm_tpl->addToAcls(
                    $series->getAccessPolicies(),
                    !$objectSettings->getStreamingOnly(),
                    $objectSettings->getUseAnnotations()
                )
            );
        }

        /** @var Metadata $metadata */
        $metadata = $data['metadata']['object'];
        $this->seriesRepository->updateMetadata(
            new UpdateSeriesMetadataRequest(
                $this->objectSettings->getSeriesIdentifier(),
                new UpdateSeriesMetadataRequestPayload($metadata)
            )
        );
        $this->seriesRepository->updateACL(
            new UpdateSeriesACLRequest(
                $this->objectSettings->getSeriesIdentifier(),
                new UpdateSeriesACLRequestPayload($series->getAccessPolicies())
            )
        );

        $this->object->updateObjectFromSeries($metadata);

        $this->objectSettings->updateAllDuplicates($metadata);
        ilUtil::sendSuccess($this->plugin->txt('series_saved'), true);
        $this->ctrl->redirect($this, self::CMD_EDIT_GENERAL);
    }

    /**
     * @return void
     * @throws DICException
     * @throws ilException
     */
    protected function editWorkflowParameters()
    {
        $this->seriesWorkflowParameterRepository->syncAvailableParameters($this->getObjId());
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo($this->plugin->txt('series_has_duplicates'));
        }
        $this->tabs->activateSubTab(self::SUBTAB_WORKFLOW_PARAMETERS);

        $xoctSeriesFormGUI = new xoctSeriesWorkflowParameterTableGUI(
            $this,
            self::CMD_EDIT_WORKFLOW_PARAMS,
            $this->workflowParameterRepository
        );
        $this->main_tpl->setContent($xoctSeriesFormGUI->getHTML());
    }

    /**
     * @throws DICException
     */
    protected function updateWorkflowParameters()
    {
        foreach (
            filter_input(
                INPUT_POST,
                'workflow_parameter',
                FILTER_DEFAULT,
                FILTER_REQUIRE_ARRAY
            ) as $param_id => $value
        ) {
            $value_admin = $value['value_admin'];
            $value_member = $value['value_member'];
            if (in_array($value_member, WorkflowParameter::$possible_values) && in_array(
                $value_admin,
                WorkflowParameter::$possible_values
            )) {
                SeriesWorkflowParameterRepository::getByObjAndParamId(
                    $this->getObjId(),
                    $param_id
                )->setDefaultValueAdmin($value_admin)->setValueMember($value_member)->update();
            }
        }
        ilUtil::sendSuccess($this->plugin->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_EDIT_WORKFLOW_PARAMS);
    }

    /**
     *
     */
    protected function cancel()
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

    /**
     *
     */
    protected function add()
    {
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
    protected function confirmDelete()
    {
    }

    /**
     *
     */
    protected function delete()
    {
    }

    /**
     *
     */
    protected function view()
    {
    }
}
