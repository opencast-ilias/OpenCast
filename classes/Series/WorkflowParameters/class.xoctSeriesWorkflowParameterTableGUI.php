<?php

use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameter;

/**
 * Class xoctSeriesWorkflowParameterTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParameterTableGUI extends TableGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    public const ROW_TEMPLATE = "tpl.series_workflow_parameter_table_row.html";
    /**
     * @var xoctSeriesGUI
     */
    protected $parent_obj;
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;

    /**
     * xoctSeriesWorkflowParameterTableGUI constructor.
     *
     * @param $parent
     * @param $parent_cmd
     */
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
        $this->addCommandButton(xoctSeriesGUI::CMD_UPDATE_WORKFLOW_PARAMS, $this->lng->txt('save'));
    }

    /**
     * @param string $column
     * @param array  $row
     * @param        $format
     *
     * @return string
     */
    protected function getColumnValue(string $column, /*array*/ $row, int $format = self::DEFAULT_FORMAT): string
    {
        switch ($column) {
            default:
                $column = $row[$column];
                break;
        }

        return strval($column);
    }

    /**
     * @return array
     */
    protected function getSelectableColumns2(): array
    {
        return [];
    }

    /**
     *
     */
    protected function initColumns(): void
    {
        $this->addColumn($this->lng->txt("id"));
        $this->addColumn($this->lng->txt("title"));
        $this->addColumn($this->lng->txt("type"));
        $this->addColumn(self::plugin()->translate("value_member"));
        $this->addColumn(self::plugin()->translate("value_admin"));
        $this->addColumn('', '', '', true);
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

    /**
     *
     */
    protected function initData(): void
    {
        $this->setData(
            SeriesWorkflowParameter::innerjoin(WorkflowParameter::TABLE_NAME, 'param_id', 'id')->where(
                ['obj_id' => $this->parent_obj->getObjId()]
            )->getArray()
        );
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
     *
     */
    protected function initTitle(): void
    {
    }
}
