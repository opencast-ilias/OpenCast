<?php

declare(strict_types=1);

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctWorkflowParameterFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterFormGUI extends ilPropertyFormGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'workflow_params' : $module, $fallback);
    }

    public const PROPERTY_TITLE = 'setTitle';

    public const F_ID = 'id';
    public const F_TITLE = 'title';
    public const F_TYPE = 'type';
    public const F_DEFAULT_VALUE_MEMBER = 'default_value_member';
    public const F_DEFAULT_VALUE_ADMIN = 'default_value_admin';
    private \xoctWorkflowParameterGUI $parent;

    /**
     * @var WorkflowParameter
     */
    protected $xoctWorkflowParameter;
    private WorkflowParameterRepository $workflowParameterRepository;

    public function __construct(xoctWorkflowParameterGUI $parent, WorkflowParameterRepository $workflowParameterRepository, string $param_id = null)
    {
        $this->parent = $parent;
        $this->xoctWorkflowParameter = WorkflowParameter::findOrGetInstance($param_id);
        $this->workflowParameterRepository = $workflowParameterRepository;
        parent::__construct();
        $this->initForm();
    }

    private function initForm(): void
    {
        $this->initAction();
        $this->initCommands();
        $this->initTitle();
        $this->initFields();
    }

    protected function initAction(): void
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent));
    }


    protected function initCommands(): void
    {
        $this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_PARAMETER, $this->getLocaleString('save', 'common'));
        $this->addCommandButton(xoctGUI::CMD_CANCEL, $this->getLocaleString('cancel', 'common'));
    }

    protected function initFields(): void
    {
        $field = new ilTextInputGUI($this->getLocaleString(self::F_ID), self::F_ID);
        $field->setRequired(true);
        $field->setValue($this->xoctWorkflowParameter->getId());
        $this->addItem($field);

        $field = new ilTextInputGUI($this->getLocaleString(self::F_TITLE), self::F_TITLE);
        $field->setRequired(true);
        $field->setValue($this->xoctWorkflowParameter->getTitle());
        $this->addItem($field);

        $field = new ilSelectInputGUI($this->getLocaleString(self::F_TYPE), self::F_TYPE);
        $field->setRequired(true);
        $field->setValue($this->xoctWorkflowParameter->getType());
        $field->setOptions([
            WorkflowParameter::TYPE_CHECKBOX => 'Checkbox'
        ]);
        $this->addItem($field);

        $field = new ilSelectInputGUI($this->getLocaleString(self::F_DEFAULT_VALUE_MEMBER), self::F_DEFAULT_VALUE_MEMBER);
        $field->setRequired(true);
        $field->setValue($this->xoctWorkflowParameter->getDefaultValueMember());
        $field->setOptions($this->workflowParameterRepository->getSelectionOptions());
        $this->addItem($field);

        $field = new ilSelectInputGUI($this->getLocaleString(self::F_DEFAULT_VALUE_ADMIN), self::F_DEFAULT_VALUE_ADMIN);
        $field->setRequired(true);
        $field->setValue($this->xoctWorkflowParameter->getDefaultValueAdmin());
        $field->setOptions($this->workflowParameterRepository->getSelectionOptions());
        $this->addItem($field);
    }

    protected function initTitle(): void
    {
        $this->setTitle($this->getLocaleString('table_title'));
    }

    public function storeForm(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }

        $this->workflowParameterRepository->createOrUpdate(
            $this->getInput(self::F_ID),
            $this->getInput(self::F_TITLE),
            $this->getInput(self::F_TYPE),
            $this->getInput(self::F_DEFAULT_VALUE_MEMBER),
            $this->getInput(self::F_DEFAULT_VALUE_ADMIN)
        );

        return true;
    }
}
