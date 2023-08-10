<?php

use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;

/**
 * Class xoctWorkflowParameterGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowParameterGUI : xoctMainGUI
 */
class xoctWorkflowParameterGUI extends xoctGUI
{
    public const SUBTAB_PARAMETERS = 'parameters';
    public const SUBTAB_SETTINGS = 'settings';

    public const CMD_SHOW_TABLE = 'showTable';
    public const CMD_SHOW_FORM = 'showForm';
    public const CMD_UPDATE_FORM = 'updateForm';
    public const CMD_UPDATE_PARAMETER = 'updateParameter';
    public const CMD_UPDATE_TABLE = 'updateTable';
    public const CMD_LOAD_WORKFLOW_PARAMS = 'loadWorkflowParameters';
    public const CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED = 'loadWorkflowParametersConfirmed';
    public const CMD_OVERWRITE_SERIES_PARAMETERS = 'overwriteSeriesParameters';
    /**
     * @var bool
     */
    protected $overwrite_series_parameters = false;
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $seriesWorkflowParameterRepository;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ilLanguage
     */
    private $language;

    /**
     * @param WorkflowParameterRepository $workflowParameterRepository
     */
    public function __construct(
        WorkflowParameterRepository $workflowParameterRepository,
        SeriesWorkflowParameterRepository $seriesWorkflowParameterRepository
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();
        $this->language = $DIC->language();
        $this->workflowParameterRepository = $workflowParameterRepository;
        $this->seriesWorkflowParameterRepository = $seriesWorkflowParameterRepository;
    }

    /**
     *
     */
    protected function index()
    {
        $this->showTable();
    }

    /**
     * @throws DICException
     */
    protected function setSubTabs()
    {
        $this->tabs->addSubTab(
            self::SUBTAB_PARAMETERS,
            $this->plugin->txt(self::SUBTAB_PARAMETERS),
            $this->ctrl->getLinkTarget($this, self::CMD_STANDARD)
        );
        $this->tabs->addSubTab(
            self::SUBTAB_SETTINGS,
            $this->plugin->txt(self::SUBTAB_SETTINGS),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOW_FORM)
        );
    }

    /**
     *
     */
    protected function showTable()
    {
        ilUtil::sendInfo($this->plugin->txt('msg_workflow_parameters_info'));
        $this->tabs->setSubTabActive(self::SUBTAB_PARAMETERS);
        $xoctWorkflowParameterTableGUI = new xoctWorkflowParameterTableGUI(
            $this,
            self::CMD_SHOW_TABLE,
            $this->workflowParameterRepository
        );
        $this->main_tpl->setContent($xoctWorkflowParameterTableGUI->getHTML());
    }

    /**
     *
     */
    protected function showForm()
    {
        $this->tabs->setSubTabActive(self::SUBTAB_SETTINGS);
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    /**
     * @param $cmd
     */
    protected function performCommand($cmd)
    {
        $this->initToolbar($cmd);
        $this->setSubTabs();
        $this->{$cmd}();
    }

    /**
     *
     */
    protected function initToolbar($cmd)
    {
        switch ($cmd) {
            case self::CMD_STANDARD:
            case self::CMD_SHOW_TABLE:
                $button = ilLinkButton::getInstance();
                $button->setCaption($this->plugin->txt('config_btn_load_parameters'), false);
                $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_LOAD_WORKFLOW_PARAMS));
                $this->toolbar->addButtonInstance($button);
                $button = ilLinkButton::getInstance();
                $button->setCaption($this->plugin->txt('config_btn_add_parameter'), false);
                $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
                $button->setPrimary(true);
                $this->toolbar->addButtonInstance($button);
                break;
            default:
                break;
        }
    }

    /**
     *
     */
    protected function loadWorkflowParameters()
    {
        try {
            $params = $this->workflowParameterRepository->loadParametersFromAPI();
            if (!count($params)) {
                ilUtil::sendFailure($this->plugin->txt('msg_no_params_found'), true);
                $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
            }
            $ilConfirmationGUI = new ilConfirmationGUI();
            $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
            $ilConfirmationGUI->setCancel($this->language->txt('cancel'), self::CMD_SHOW_TABLE);
            $ilConfirmationGUI->setConfirm($this->language->txt('confirm'), self::CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED);
            $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_load_workflow_params'));
            /** @var WorkflowParameter $param */
            foreach ($params as $param) {
                $ilConfirmationGUI->addItem(
                    'workflow_params[' . $param->getId() . '][title]',
                    $param->getTitle(),
                    $param->getTitle()
                );
                $ilConfirmationGUI->addHiddenItem('workflow_params[' . $param->getId() . '][type]', $param->getType());
            }
            $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
        } catch (xoctException $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
        }
    }

    /**
     *
     */
    protected function loadWorkflowParametersConfirmed()
    {
        $existing_ids = WorkflowParameter::getArray(null, 'id');
        $delivered_params = filter_input(INPUT_POST, 'workflow_params', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $delivered_ids = array_keys($delivered_params);
        $to_delete_ids = array_diff($existing_ids, $delivered_ids);
        $to_create_ids = array_diff($delivered_ids, $existing_ids);
        $to_create = [];

        // create new and update existing
        foreach ($delivered_params as $param_id => $parameter) {
            $xoctWorkflowParameter = $this->workflowParameterRepository->createOrUpdate(
                $param_id,
                $parameter['title'],
                $parameter['type']
            );
            if (in_array($param_id, $to_create_ids)) {
                $to_create[] = $xoctWorkflowParameter;
            }
        }

        // delete not delivered
        foreach ($to_delete_ids as $id_to_delete) {
            WorkflowParameter::find($id_to_delete)->delete();
        }

        // create/delete the series settings
        if (count($to_delete_ids)) {
            $this->seriesWorkflowParameterRepository->deleteParamsForAllObjectsById($to_delete_ids);
        }
        if (count($to_create_ids)) {
            $this->seriesWorkflowParameterRepository->createParamsForAllObjects($to_create);
        }

        ilUtil::sendSuccess($this->plugin->txt('config_msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
    }

    /**
     *
     */
    protected function edit()
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI(
            $this,
            $this->workflowParameterRepository,
            filter_input(INPUT_GET, 'param_id')
        );
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    /**
     *
     */
    protected function add()
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this, $this->workflowParameterRepository);
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    /**
     *
     */
    protected function create()
    {
        $this->updateParameter();
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
    protected function updateForm()
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
        $xoctWorkflowParameterFormGUI->setValuesByPost();
        if ($xoctWorkflowParameterFormGUI->storeForm()) {
            ilUtil::sendSuccess($this->plugin->txt('config_msg_success'), true);
            if ($this->overwrite_series_parameters) {
                $ilConfirmationGUI = new ilConfirmationGUI();
                $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
                $ilConfirmationGUI->setCancel($this->language->txt('cancel'), self::CMD_STANDARD);
                $ilConfirmationGUI->setConfirm($this->language->txt('confirm'), self::CMD_OVERWRITE_SERIES_PARAMETERS);
                $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_confirm_overwrite_series_params'));
                $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
            } else {
                $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
            }
        } else {
            $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
        }
    }

    /**
     *
     */
    protected function updateParameter()
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this, $this->workflowParameterRepository);
        $xoctWorkflowParameterFormGUI->setValuesByPost();
        if ($xoctWorkflowParameterFormGUI->storeForm()) {
            ilUtil::sendSuccess($this->plugin->txt('config_msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
        }
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    /**
     * @throws DICException
     */
    protected function updateTable()
    {
        foreach (
            filter_input(
                INPUT_POST,
                'workflow_parameter',
                FILTER_DEFAULT,
                FILTER_REQUIRE_ARRAY
            ) as $id => $value
        ) {
            $default_value_admin = $value['default_value_admin'];
            $default_value_member = $value['default_value_member'];
            if (in_array($default_value_member, WorkflowParameter::$possible_values) && in_array(
                $default_value_admin,
                WorkflowParameter::$possible_values
            )) {
                WorkflowParameter::find($id)->setDefaultValueAdmin($default_value_admin)->setDefaultValueMember(
                    $default_value_member
                )->update();
            }
        }
        ilUtil::sendSuccess($this->plugin->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
    }

    /**
     *
     */
    protected function overwriteSeriesParameters()
    {
        $this->workflowParameterRepository->overwriteSeriesParameter();
        ilUtil::sendSuccess($this->plugin->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
    }

    /**
     *
     */
    protected function confirmDelete()
    {
        $this->workflowParameterRepository->deleteById($_POST['param_id']);
        ilUtil::sendSuccess($this->plugin->txt('config_msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
    }

    /**
     *
     */
    protected function delete()
    {
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->setConfirm($this->language->txt('confirm'), self::CMD_CONFIRM);
        $ilConfirmationGUI->setCancel($this->language->txt('cancel'), self::CMD_SHOW_TABLE);
        $ilConfirmationGUI->addItem(
            'param_id',
            $_GET['param_id'],
            WorkflowParameter::find($_GET['param_id'])->getTitle()
        );
        $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_confirm_delete_param'));
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    /**
     *
     */
    public function setOverwriteSeriesParameter()
    {
        $this->overwrite_series_parameters = true;
    }
}
