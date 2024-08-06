<?php

declare(strict_types=1);
use srag\Plugins\Opencast\Container\Init;

use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameter;

/**
 * Class xoctSeriesWorkflowParameterTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParameterTableGUI extends ilTable2GUI
{
    use \srag\Plugins\Opencast\LegacyHelpers\TableGUI;

    public const ROW_TEMPLATE = "tpl.series_workflow_parameter_table_row.html";

    protected ilOpenCastPlugin $plugin;

    /**
     * xoctSeriesWorkflowParameterTableGUI constructor.
     *
     * @param $parent
     * @param $parent_cmd
     */
    public function __construct(?object $parent, string $parent_cmd, private WorkflowParameterRepository $workflowParameterRepository)
    {
        global $DIC;
        $opencastContainer = Init::init();
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        parent::__construct($parent, $parent_cmd);
        $this->initTable();
        $this->setEnableNumInfo(false);
    }

    protected function getRowTemplate(): string
    {
        return $this->plugin->getDirectory() . '/templates/default/' . self::ROW_TEMPLATE;
    }

    protected function initCommands(): void
    {
        $this->addCommandButton(xoctSeriesGUI::CMD_UPDATE_WORKFLOW_PARAMS, $this->lng->txt('save'));
    }

    protected function getColumnValue(string $column, /*array*/ $row, int $format = 0): string
    {
        return $row[$column] ?? '';
    }

    protected function getSelectableColumns2(): array
    {
        return [];
    }

    protected function initColumns(): void
    {
        $this->addColumn($this->lng->txt("id"));
        $this->addColumn($this->lng->txt("title"));
        $this->addColumn($this->lng->txt("type"));
        $this->addColumn($this->plugin->txt("workflow_params_default_value_member"));
        $this->addColumn($this->plugin->txt("workflow_params_default_value_admin"));
        $this->addColumn('', '', '', true);
    }

    protected function fillRow(array $row): void
    {
        $this->tpl->setVariable("ID", $row["id"]);
        $this->tpl->setVariable("TITLE", $row["title"]);
        $this->tpl->setVariable("TYPE", $row["type"]);

        $ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][value_member]');
        $ilSelectInputGUI->setOptions($this->workflowParameterRepository->getSelectionOptions());
        $ilSelectInputGUI->setValue($row['value_member']);
        $this->tpl->setVariable("VALUE_MEMBER", $ilSelectInputGUI->getToolbarHTML());

        $ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][value_admin]');
        $ilSelectInputGUI->setOptions($this->workflowParameterRepository->getSelectionOptions());
        $ilSelectInputGUI->setValue($row['value_admin']);
        $this->tpl->setVariable("VALUE_ADMIN", $ilSelectInputGUI->getToolbarHTML());

        $this->ctrl->setParameter($this->parent_obj, "xhfp_content", null);
    }

    protected function initData(): void
    {
        $this->setData(
            SeriesWorkflowParameter::innerjoin(WorkflowParameter::TABLE_NAME, 'param_id', 'id')->where(
                ['obj_id' => $this->parent_obj->getObjId()]
            )->getArray()
        );
    }

    protected function initId(): void
    {
    }

    protected function initTitle(): void
    {
    }
}
