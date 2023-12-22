<?php

declare(strict_types=1);

use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameterRepository;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\CustomInputGUIs\OpenCast\Template\Template;
use srag\Plugins\Opencast\LegacyHelpers\TableGUI as LegacyTableGUI;
use srag\Plugins\Opencast\LegacyHelpers\TableGUIConstants;

/**
 * Class xoctWorkflowParameterTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterTableGUI extends ilTable2GUI
{
    use LegacyTableGUI;
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'workflow_params' : $module, $fallback);
    }

    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class; // TODO remove

    public const ROW_TEMPLATE = "tpl.workflow_parameter_table_row.html";
    /**
     * @var WorkflowParameterRepository
     */
    private $workflowParameterRepository;
    /**
     * @var ilOpenCastPlugin
     */
    private $plugin;

    public function __construct($parent, string $parent_cmd, WorkflowParameterRepository $workflowParameterRepository)
    {
        global /** @var Container $opencastContainer */
        $DIC, $opencastContainer;
        $this->plugin = $opencastContainer->get(ilOpenCastPlugin::class);
        parent::__construct($parent, $parent_cmd);
        $this->initTable();
        $this->setEnableNumInfo(false);
        $this->workflowParameterRepository = $workflowParameterRepository;
    }

    protected function initCommands(): void
    {
        $this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_TABLE, $this->getLocaleString('save', 'series'));
    }

    /**
     * @inheritdoc
     */
    protected function initColumns(): void
    {
        $this->addColumn($this->getLocaleString("id", 'workflow_params'));
        $this->addColumn($this->getLocaleString("title", 'workflow_params'));
        $this->addColumn($this->getLocaleString("type", 'workflow_params'));
        $this->addColumn($this->getLocaleString("default_value_member", ''));
        $this->addColumn($this->getLocaleString("default_value_admin", ''));
        $this->addColumn('', '', '', false);
    }


    protected function initData(): void
    {
        $this->setData(WorkflowParameter::getArray());
    }

    protected function getColumnValue(string $column, $row, int $format = TableGUIConstants::DEFAULT_FORMAT): string
    {
        return $row[$column];
    }

    protected function getSelectableColumns2(): array
    {
        return [];
    }

    protected function initId(): void
    {
        $this->setId('xoct_workflow_parameter');
    }

    protected function initTitle(): void
    {
        $this->setTitle($this->getLocaleString('table_title', 'workflow_params'));
    }

    protected function getRowTemplate(): string
    {
        return $this->plugin->getDirectory() . '/templates/default/' . self::ROW_TEMPLATE;
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
        $actions->setListTitle($this->getLocaleString("actions", 'common'));

        $this->ctrl->setParameterByClass(xoctWorkflowParameterGUI::class, 'param_id', $row["id"]);

        $actions->addItem(
            $this->getLocaleString("edit", 'common'),
            "",
            $this->ctrl
                ->getLinkTarget($this->parent_obj, xoctGUI::CMD_EDIT)
        );

        $actions->addItem(
            $this->getLocaleString("delete", 'common'),
            "",
            $this->ctrl
                ->getLinkTarget($this->parent_obj, xoctGUI::CMD_DELETE)
        );

        $this->tpl->setVariable("ACTIONS", $actions->getHTML());

        $this->ctrl->setParameter($this->parent_obj, "xhfp_content", null);
    }
}
