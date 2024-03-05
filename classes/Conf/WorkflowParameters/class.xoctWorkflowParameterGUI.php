<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctWorkflowParameterGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctWorkflowParameterGUI : xoctMainGUI
 */
class xoctWorkflowParameterGUI extends xoctGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'workflow_params' : $module, $fallback);
    }

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
     * @var \ilToolbarGUI
     */
    private $toolbar;

    public function __construct(
        WorkflowParameterRepository $workflowParameterRepository,
        SeriesWorkflowParameterRepository $seriesWorkflowParameterRepository
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->workflowParameterRepository = $workflowParameterRepository;
        $this->seriesWorkflowParameterRepository = $seriesWorkflowParameterRepository;
    }

    protected function index(): void
    {
        $this->showTable();
    }

    protected function setSubTabs(): void
    {
        $this->tabs->addSubTab(
            self::SUBTAB_PARAMETERS,
            $this->getLocaleString(self::SUBTAB_PARAMETERS, 'subtab'),
            $this->ctrl->getLinkTarget($this, self::CMD_STANDARD)
        );
        $this->tabs->addSubTab(
            self::SUBTAB_SETTINGS,
            $this->getLocaleString(self::SUBTAB_SETTINGS, 'subtab'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOW_FORM)
        );
    }

    protected function showTable(): void
    {
        $this->main_tpl->setOnScreenMessage('info', $this->getLocaleString('parameters_info', 'msg_workflow'));
        $this->tabs->setSubTabActive(self::SUBTAB_PARAMETERS);
        $xoctWorkflowParameterTableGUI = new xoctWorkflowParameterTableGUI(
            $this,
            self::CMD_SHOW_TABLE,
            $this->workflowParameterRepository
        );
        $this->main_tpl->setContent($xoctWorkflowParameterTableGUI->getHTML());
    }

    protected function showForm(): void
    {
        $this->tabs->setSubTabActive(self::SUBTAB_SETTINGS);
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    protected function performCommand(string $cmd): void
    {
        $this->initToolbar($cmd);
        $this->setSubTabs();
        $this->{$cmd}();
    }

    protected function initToolbar(string $cmd): void
    {
        switch ($cmd) {
            case self::CMD_STANDARD:
            case self::CMD_SHOW_TABLE:
                $button = ilLinkButton::getInstance();
                $button->setCaption($this->getLocaleString('load_parameters', 'config_btn'), false);
                $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_LOAD_WORKFLOW_PARAMS));
                $this->toolbar->addButtonInstance($button);
                $button = ilLinkButton::getInstance();
                $button->setCaption($this->getLocaleString('add_parameter', 'config_btn'), false);
                $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
                $button->setPrimary(true);
                $this->toolbar->addButtonInstance($button);
                break;
            default:
                break;
        }
    }

    protected function loadWorkflowParameters(): void
    {
        try {
            $params = $this->workflowParameterRepository->loadParametersFromAPI();
            if ($params === []) {
                $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString('msg_no_params_found'), true);
                $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
            }
            $ilConfirmationGUI = new ilConfirmationGUI();
            $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
            $ilConfirmationGUI->setCancel($this->getLocaleString('cancel', 'common'), self::CMD_SHOW_TABLE);
            $ilConfirmationGUI->setConfirm($this->getLocaleString('confirm', 'common'), self::CMD_LOAD_WORKFLOW_PARAMS_CONFIRMED);
            $ilConfirmationGUI->setHeaderText($this->getLocaleString('msg_load_workflow_params'));
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
            $this->main_tpl->setOnScreenMessage('failure', $e->getMessage(), true);
            $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
        }
    }

    protected function loadWorkflowParametersConfirmed(): void
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
        if ($to_delete_ids !== []) {
            $this->seriesWorkflowParameterRepository->deleteParamsForAllObjectsById($to_delete_ids);
        }
        if ($to_create_ids !== []) {
            $this->seriesWorkflowParameterRepository->createParamsForAllObjects($to_create);
        }

        $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
    }

    protected function edit(): void
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI(
            $this,
            $this->workflowParameterRepository,
            filter_input(INPUT_GET, 'param_id')
        );
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    protected function add(): void
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this, $this->workflowParameterRepository);
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    protected function create(): void
    {
        $this->updateParameter();
    }

    protected function update(): void
    {
    }

    protected function updateForm(): void
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParametersFormGUI($this);
        $xoctWorkflowParameterFormGUI->setValuesByPost();
        if ($xoctWorkflowParameterFormGUI->storeForm()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
            if ($this->overwrite_series_parameters) {
                $ilConfirmationGUI = new ilConfirmationGUI();
                $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
                $ilConfirmationGUI->setCancel($this->getLocaleString('cancel'), self::CMD_STANDARD);
                $ilConfirmationGUI->setConfirm(
                    $this->getLocaleString('confirm'),
                    self::CMD_OVERWRITE_SERIES_PARAMETERS
                );
                $ilConfirmationGUI->setHeaderText($this->getLocaleString('msg_confirm_overwrite_series_params'));
                $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
            } else {
                $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
            }
        } else {
            $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
        }
    }

    protected function updateParameter(): void
    {
        $xoctWorkflowParameterFormGUI = new xoctWorkflowParameterFormGUI($this, $this->workflowParameterRepository);
        $xoctWorkflowParameterFormGUI->setValuesByPost();
        if ($xoctWorkflowParameterFormGUI->storeForm()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
            $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
        }
        $this->main_tpl->setContent($xoctWorkflowParameterFormGUI->getHTML());
    }

    protected function updateTable(): void
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
        $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
    }

    protected function overwriteSeriesParameters(): void
    {
        $this->workflowParameterRepository->overwriteSeriesParameter();
        $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
    }

    protected function confirmDelete(): void
    {
        $this->workflowParameterRepository->deleteById(
            (int) ($this->http->request()->getParsedBody()['param_id'] ?? 0)
        );
        $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_TABLE);
    }

    protected function delete(): void
    {
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->setConfirm($this->getLocaleString('confirm'), self::CMD_CONFIRM);
        $ilConfirmationGUI->setCancel($this->getLocaleString('cancel'), self::CMD_SHOW_TABLE);
        $param_id = (int) ($this->http->request()->getQueryParams()['param_id'] ?? 0);
        $ilConfirmationGUI->addItem(
            'param_id',
            (string) $param_id,
            WorkflowParameter::find($param_id)->getTitle() ?? ''
        );
        $ilConfirmationGUI->setHeaderText($this->getLocaleString('confirm_delete_param', 'msg'));
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    public function setOverwriteSeriesParameter(): void
    {
        $this->overwrite_series_parameters = true;
    }
}
