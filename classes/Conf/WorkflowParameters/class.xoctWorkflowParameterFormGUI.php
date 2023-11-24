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
class xoctWorkflowParameterFormGUI extends PropertyFormGUI
{
    use LocaleTrait;

    public const PROPERTY_TITLE = 'setTitle';

    public const F_ID = 'id';
    public const F_TITLE = 'title';
    public const F_TYPE = 'type';
    public const F_DEFAULT_VALUE_MEMBER = 'default_value_member';
    public const F_DEFAULT_VALUE_ADMIN = 'default_value_admin';

    /**
     * @var WorkflowParameter
     */
    protected $xoctWorkflowParameter;
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;

    public function __construct($parent, WorkflowParameterRepository $workflowParameterRepository, $param_id = '')
    {
        $this->xoctWorkflowParameter = WorkflowParameter::findOrGetInstance($param_id);
        $this->workflowParameterRepository = $workflowParameterRepository;
        parent::__construct($parent);
    }

    /**
     * @return mixed|void
     */
    protected function getValue(string $key)
    {
    }

    /**
     *
     */
    protected function initCommands(): void
    {
        $this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_PARAMETER, $this->getLocaleString('save'));
        $this->addCommandButton(xoctGUI::CMD_CANCEL, $this->getLocaleString('cancel'));
    }

    /**
     *
     */
    protected function initFields(): void
    {
        $this->fields = [
            self::F_ID => [
                self::PROPERTY_TITLE => $this->getLocaleString(self::F_ID),
                self::PROPERTY_CLASS => ilTextInputGUI::class,
                self::PROPERTY_REQUIRED => true,
                self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getId()
            ],
            self::F_TITLE => [
                self::PROPERTY_TITLE => $this->getLocaleString(self::F_TITLE),
                self::PROPERTY_CLASS => ilTextInputGUI::class,
                self::PROPERTY_REQUIRED => true,
                self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getTitle()
            ],
            self::F_TYPE => [
                self::PROPERTY_TITLE => $this->getLocaleString(self::F_TYPE),
                self::PROPERTY_CLASS => ilSelectInputGUI::class,
                self::PROPERTY_REQUIRED => true,
                self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getType(),
                self::PROPERTY_OPTIONS => [
                    WorkflowParameter::TYPE_CHECKBOX => 'Checkbox'
                ]
            ],
            self::F_DEFAULT_VALUE_MEMBER => [
                self::PROPERTY_TITLE => $this->getLocaleString(self::F_DEFAULT_VALUE_MEMBER),
                self::PROPERTY_CLASS => ilSelectInputGUI::class,
                self::PROPERTY_REQUIRED => true,
                self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getDefaultValueMember(),
                self::PROPERTY_OPTIONS => $this->workflowParameterRepository->getSelectionOptions()
            ],
            self::F_DEFAULT_VALUE_ADMIN => [
                self::PROPERTY_TITLE => $this->getLocaleString(self::F_DEFAULT_VALUE_ADMIN),
                self::PROPERTY_CLASS => ilSelectInputGUI::class,
                self::PROPERTY_REQUIRED => true,
                self::PROPERTY_VALUE => $this->xoctWorkflowParameter->getDefaultValueAdmin(),
                self::PROPERTY_OPTIONS => $this->workflowParameterRepository->getSelectionOptions()
            ],

        ];
    }


    protected function initId(): void
    {
    }

    /**
     *
     */
    protected function initTitle(): void
    {
        $this->setTitle($this->getLocaleString('edit'));
    }

    public function storeForm(): bool
    {
        if (!$this->storeFormCheck()) {
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

    /**
     * @param mixed $value
     */
    protected function storeValue(string $key, $value): void
    {
    }
}
