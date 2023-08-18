<?php

use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;

/**
 * Class xoctWorkflowParameterTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterTableGUI extends TableGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    public const ROW_TEMPLATE = "tpl.workflow_parameter_table_row.html";
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;

    public function __construct($parent, string $parent_cmd, WorkflowParameterRepository $workflowParameterRepository)
    {
        parent::__construct($parent, $parent_cmd);
        $this->setEnableNumInfo(false);
        $this->workflowParameterRepository = $workflowParameterRepository;
    }

    /**
     *
     */
    protected function initCommands(): void
    {
        $this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_TABLE, $this->lng->txt('save'));
    }

    /**
     * @inheritdoc
     */
    protected function initColumns(): void
    {
        $this->addColumn($this->lng->txt("id"));
        $this->addColumn($this->lng->txt("title"));
        $this->addColumn($this->lng->txt("type"));
        $this->addColumn(self::plugin()->translate("default_value_member"));
        $this->addColumn(self::plugin()->translate("default_value_admin"));
        $this->addColumn('', '', '', true);
    }

    /**
     * @param array  $row
     * @param        $format
     *
     */
    protected function getColumnValue(string $column, /*array*/ $row, int $format = self::DEFAULT_FORMAT): string
    {
        $column = $row[$column];
    }

    protected function getSelectableColumns2(): array
    {
        return [];
    }

    /**
     *
     */
    protected function initData(): void
    {
        $this->setData(WorkflowParameter::getArray());
    }

    /**
     *
     */
    protected function initFilterFields(): void
    {
    }

    /**
     *
     */
    protected function initId(): void
    {
    }

    /**
     * @throws \srag\DIC\OpenCast\Exception\DICException
     */
    protected function initTitle(): void
    {
        $this->setTitle(self::plugin()->translate('workflow_parameters'));
    }

    /**
     * @param array $row
     *
     * @throws \srag\DIC\OpenCast\Exception\DICException
     * @throws ilTemplateException
     */
    protected function fillRow($row): void
    {
        $this->tpl->setVariable("ID", $row["id"]);
        $this->tpl->setVariable("TITLE", $row["title"]);
        $this->tpl->setVariable("TYPE", $row["type"]);

        $ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][default_value_member]');
        $ilSelectInputGUI->setOptions($this->workflowParameterRepository->getSelectionOptions());
        $ilSelectInputGUI->setValue($row['default_value_member']);
        $this->tpl->setVariable("DEFAULT_VALUE_MEMBER", $ilSelectInputGUI->getToolbarHTML());

        $ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][default_value_admin]');
        $ilSelectInputGUI->setOptions($this->workflowParameterRepository->getSelectionOptions());
        $ilSelectInputGUI->setValue($row['default_value_admin']);
        $this->tpl->setVariable("DEFAULT_VALUE_ADMIN", $ilSelectInputGUI->getToolbarHTML());

        $actions = new ilAdvancedSelectionListGUI();
        $actions->setListTitle($this->lng->txt("actions"));

        $this->ctrl->setParameterByClass(xoctWorkflowParameterGUI::class, 'param_id', $row["id"]);

        $actions->addItem(
            $this->lng->txt("edit"),
            "",
            $this->ctrl
                ->getLinkTarget($this->parent_obj, xoctWorkflowParameterGUI::CMD_EDIT)
        );

        $actions->addItem(
            $this->lng->txt("delete"),
            "",
            $this->ctrl
                ->getLinkTarget($this->parent_obj, xoctWorkflowParameterGUI::CMD_DELETE)
        );

        $this->tpl->setVariable("ACTIONS", self::output()->getHTML($actions));

        $this->ctrl->setParameter($this->parent_obj, "xhfp_content", null);
    }
}
