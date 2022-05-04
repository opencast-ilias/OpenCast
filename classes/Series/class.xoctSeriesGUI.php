<?php

use ILIAS\UI\Component\Input\Field\UploadHandler;
use srag\DIC\OpencastObject\Exception\DICException;
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
 * @ilCtrl_IsCalledBy xoctSeriesGUI : ilObjOpencastObjectGUI
 */
class xoctSeriesGUI extends xoctGUI
{

    const SERIES_ID = 'series_id';

    const CMD_EDIT_GENERAL = 'editGeneral';
    const CMD_EDIT = self::CMD_EDIT_GENERAL;
    const CMD_EDIT_WORKFLOW_PARAMS = 'editWorkflowParameters';
    const CMD_UPDATE_GENERAL = 'updateGeneral';
    const CMD_UPDATE = self::CMD_UPDATE_GENERAL;
    const CMD_UPDATE_WORKFLOW_PARAMS = 'updateWorkflowParameters';

    const SUBTAB_GENERAL = 'general';
    const SUBTAB_WORKFLOW_PARAMETERS = 'workflow_params';

    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var ilObjOpencastObject
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

    public function __construct(ilObjOpencastObject                     $object,
                                SeriesFormBuilder                 $seriesFormBuilder,
                                SeriesRepository                  $seriesRepository,
                                SeriesWorkflowParameterRepository $seriesWorkflowParameterRepository,
                                WorkflowParameterRepository       $workflowParameterRepository)
    {
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
    public function executeCommand()
    {
        if (!ilObjOpencastObjectAccess::hasWriteAccess()) {
            self::dic()->ctrl()->redirectByClass('xoctEventGUI');
        }
        self::dic()->tabs()->activateTab(ilObjOpencastObjectGUI::TAB_SETTINGS);
        $this->setSubTabs();
        switch (self::dic()->ctrl()->getNextClass()) {
            default:
                parent::executeCommand();
        }
    }


    /**
     *
     */
    protected function setSubTabs()
    {
        if (PluginConfig::getConfig(PluginConfig::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
            self::dic()->ctrl()->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
            self::dic()->ctrl()->setParameter($this, 'cmd', self::CMD_EDIT_GENERAL);
            self::dic()->tabs()->addSubTab(self::SUBTAB_GENERAL, self::plugin()->translate('subtab_' . self::SUBTAB_GENERAL), self::dic()->ctrl()->getLinkTarget($this));
            self::dic()->ctrl()->setParameter($this, 'subtab_active', self::SUBTAB_WORKFLOW_PARAMETERS);
            self::dic()->ctrl()->setParameter($this, 'cmd', self::CMD_EDIT_WORKFLOW_PARAMS);
            self::dic()->tabs()->addSubTab(self::SUBTAB_WORKFLOW_PARAMETERS, self::plugin()->translate('subtab_' . self::SUBTAB_WORKFLOW_PARAMETERS), self::dic()->ctrl()->getLinkTarget($this));
        }
    }

    /**
     *
     */
    protected function index()
    {
        self::dic()->tabs()->activateTab(ilObjOpencastObjectGUI::TAB_EVENTS);
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
        if($seriesIdentifier === null) {
            return;
        }

        $series = $this->seriesRepository->find($this->objectSettings->getSeriesIdentifier());

        $this->object->updateObjectFromSeries($series->getMetadata());
        if ($this->objectSettings->getDuplicatesOnSystem()) {
            ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates'));
        }
        self::dic()->tabs()->activateSubTab(self::SUBTAB_GENERAL);
        $form = $this->seriesFormBuilder->update(self::dic()->ctrl()->getFormAction($this, self::CMD_UPDATE_GENERAL),
            $this->objectSettings,
            $series,
            ilObjOpencastObjectAccess::hasPermission('edit_videos'));
        self::dic()->ui()->mainTemplate()->setContent(self::dic()->ui()->renderer()->render($form));
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
        $form = $this->seriesFormBuilder->update(self::dic()->ctrl()->getFormAction($this),
            $this->objectSettings,
            $series,
            ilObjOpencastObjectAccess::hasPermission('edit_videos')
        )
            ->withRequest(self::dic()->http()->request());
        $data = $form->getData();
        if (!$data) {
            self::dic()->ui()->mainTemplate()->setContent(self::dic()->ui()->renderer()->render($form));
            return;
        }

        /** @var ObjectSettings $objectSettings */
        $objectSettings = $data['settings']['object'];
        $objectSettings->setObjId($this->getObjId());
        $objectSettings->setSeriesIdentifier($this->objectSettings->getSeriesIdentifier());
        $objectSettings->update();

        $perm_tpl_id = $data['settings']['permission_template'];
        $series->setAccessPolicies(PermissionTemplate::removeAllTemplatesFromAcls($series->getAccessPolicies()));
        if ($perm_tpl_id) {
            /** @var PermissionTemplate $perm_tpl */
            $perm_tpl = PermissionTemplate::find($perm_tpl_id);
            $series->setAccessPolicies($perm_tpl->addToAcls(
                $series->getAccessPolicies(),
                !$objectSettings->getStreamingOnly(),
                $objectSettings->getUseAnnotations()
            ));
        }

        /** @var Metadata $metadata */
        $metadata = $data['metadata']['object'];
        $this->seriesRepository->updateMetadata(new UpdateSeriesMetadataRequest($this->objectSettings->getSeriesIdentifier(),
            new UpdateSeriesMetadataRequestPayload($metadata)));
        $this->seriesRepository->updateACL(new UpdateSeriesACLRequest($this->objectSettings->getSeriesIdentifier(),
            new UpdateSeriesACLRequestPayload($series->getAccessPolicies())));

        $this->object->updateObjectFromSeries($metadata);

        $this->objectSettings->updateAllDuplicates($metadata);
        ilUtil::sendSuccess(self::plugin()->translate('series_saved'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_EDIT_GENERAL);
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
            ilUtil::sendInfo(self::plugin()->translate('series_has_duplicates'));
        }
        self::dic()->tabs()->activateSubTab(self::SUBTAB_WORKFLOW_PARAMETERS);

        $xoctSeriesFormGUI = new xoctSeriesWorkflowParameterTableGUI($this, self::CMD_EDIT_WORKFLOW_PARAMS, $this->workflowParameterRepository);
        self::dic()->ui()->mainTemplate()->setContent($xoctSeriesFormGUI->getHTML());
    }


    /**
     * @throws DICException
     */
    protected function updateWorkflowParameters()
    {
        foreach (filter_input(INPUT_POST, 'workflow_parameter', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) as $param_id => $value) {
            $value_admin = $value['value_admin'];
            $value_member = $value['value_member'];
            if (in_array($value_member, WorkflowParameter::$possible_values) && in_array($value_admin, WorkflowParameter::$possible_values)) {
                SeriesWorkflowParameterRepository::getByObjAndParamId($this->getObjId(), $param_id)->setDefaultValueAdmin($value_admin)->setValueMember($value_member)->update();
            }
        }
        ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_EDIT_WORKFLOW_PARAMS);
    }

    /**
     *
     */
    protected function cancel()
    {
        self::dic()->ctrl()->redirectByClass('xoctEventGUI', xoctEventGUI::CMD_STANDARD);
    }


    /**
     * @return int
     */
    public function getObjId()
    {
        return $this->objectSettings->getObjId();
    }

    public function getObject(): ilObjOpencastObject
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
