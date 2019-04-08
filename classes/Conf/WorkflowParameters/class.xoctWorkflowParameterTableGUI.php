<?php

use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;

/**
 * Class xoctWorkflowParameterTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterTableGUI extends TableGUI {

	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
	const ROW_TEMPLATE = "tpl.workflow_parameter_table_row.html";


	public function __construct($parent, $parent_cmd) {
		parent::__construct($parent, $parent_cmd);
		$this->setEnableNumInfo(false);
	}


	/**
	 *
	 */
	protected function initCommands() {
		$this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_TABLE, self::dic()->language()->txt('save'));
	}


	/**
	 * @inheritdoc
	 */
	protected function initColumns()/*: void*/ {
		$this->addColumn(self::dic()->language()->txt("id"));
		$this->addColumn(self::dic()->language()->txt("title"));
		$this->addColumn(self::dic()->language()->txt("type"));
		$this->addColumn(self::dic()->language()->txt("default_value_member"));
		$this->addColumn(self::dic()->language()->txt("default_value_admin"));
		$this->addColumn('', '', '', true);
	}


	/**
	 * @param string $column
	 * @param array  $row
	 * @param bool   $raw_export
	 *
	 * @return string
	 */
	protected function getColumnValue($column, $row, $raw_export = false) {
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
	protected function getSelectableColumns2() {
		return [];
	}


	/**
	 *
	 */
	protected function initData() {
		$this->setData(xoctWorkflowParameter::getArray());
	}


	/**
	 *
	 */
	protected function initFilterFields() {
	}


	/**
	 *
	 */
	protected function initId() {
	}


	/**
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 */
	protected function initTitle() {
		$this->setTitle(self::plugin()->translate('workflow_parameters'));
	}


	/**
	 * @param array $row
	 *
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 * @throws ilTemplateException
	 */
	protected function fillRow($row) {
		$this->tpl->setVariable("ID", $row["id"]);
		$this->tpl->setVariable("TITLE", $row["title"]);
		$this->tpl->setVariable("TYPE", $row["type"]);

		$ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][default_value_member]');
		$ilSelectInputGUI->setOptions([
			xoctWorkflowParameter::VALUE_IGNORE => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_IGNORE, 'config'),
			xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY, 'config'),
			xoctWorkflowParameter::VALUE_SHOW_IN_FORM => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SHOW_IN_FORM, 'config'),
		]);
		$ilSelectInputGUI->setValue($row['default_value_member']);
		$this->tpl->setVariable("DEFAULT_VALUE_MEMBER", $ilSelectInputGUI->getToolbarHTML());


		$ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][default_value_admin]');
		$ilSelectInputGUI->setOptions([
			xoctWorkflowParameter::VALUE_IGNORE => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_IGNORE, 'config'),
			xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY, 'config'),
			xoctWorkflowParameter::VALUE_SHOW_IN_FORM => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SHOW_IN_FORM, 'config'),
		]);
		$ilSelectInputGUI->setValue($row['default_value_admin']);
		$this->tpl->setVariable("DEFAULT_VALUE_ADMIN", $ilSelectInputGUI->getToolbarHTML());

		$actions = new ilAdvancedSelectionListGUI();
		$actions->setListTitle(self::dic()->language()->txt("actions"));

		self::dic()->ctrl()->setParameterByClass(xoctWorkflowParameterGUI::class, 'param_id', $row["id"]);

		$actions->addItem(self::dic()->language()->txt("edit"), "", self::dic()->ctrl()
			->getLinkTarget($this->parent_obj, xoctWorkflowParameterGUI::CMD_EDIT));

		$actions->addItem(self::dic()->language()->txt("delete"), "", self::dic()->ctrl()
			->getLinkTarget($this->parent_obj, xoctWorkflowParameterGUI::CMD_DELETE));

		$this->tpl->setVariable("ACTIONS", self::output()->getHTML($actions));

		self::dic()->ctrl()->setParameter($this->parent_obj, "xhfp_content", NULL);
	}
}