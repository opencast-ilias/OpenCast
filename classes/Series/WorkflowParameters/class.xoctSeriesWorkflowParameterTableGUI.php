<?php

use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;

/**
 * Class xoctSeriesWorkflowParameterTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParameterTableGUI extends TableGUI {

	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
	const ROW_TEMPLATE = "tpl.series_workflow_parameter_table_row.html";
	/**
	 * @var xoctSeriesGUI
	 */
	protected $parent_obj;


	/**
	 * xoctSeriesWorkflowParameterTableGUI constructor.
	 *
	 * @param $parent
	 * @param $parent_cmd
	 */
	public function __construct($parent, $parent_cmd) {
		parent::__construct($parent, $parent_cmd);
		$this->setEnableNumInfo(false);
	}

	/**
	 *
	 */
	protected function initCommands() {
		$this->addCommandButton(xoctSeriesGUI::CMD_UPDATE_WORKFLOW_PARAMS, self::dic()->language()->txt('save'));
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
	protected function initColumns() {
		$this->addColumn(self::dic()->language()->txt("id"));
		$this->addColumn(self::dic()->language()->txt("title"));
		$this->addColumn(self::dic()->language()->txt("type"));
		$this->addColumn(self::dic()->language()->txt("value_member"));
		$this->addColumn(self::dic()->language()->txt("value_admin"));
		$this->addColumn('', '', '', true);
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

		$ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][value_member]');
		$ilSelectInputGUI->setOptions([
			xoctWorkflowParameter::VALUE_IGNORE => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_IGNORE, 'config'),
			xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY, 'config'),
			xoctWorkflowParameter::VALUE_SHOW_IN_FORM => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SHOW_IN_FORM, 'config'),
		]);
		$ilSelectInputGUI->setValue($row['value_member']);
		$this->tpl->setVariable("VALUE_MEMBER", $ilSelectInputGUI->getToolbarHTML());


		$ilSelectInputGUI = new ilSelectInputGUI('', 'workflow_parameter[' . $row['id'] . '][value_admin]');
		$ilSelectInputGUI->setOptions([
			xoctWorkflowParameter::VALUE_IGNORE => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_IGNORE, 'config'),
			xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY, 'config'),
			xoctWorkflowParameter::VALUE_SHOW_IN_FORM => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SHOW_IN_FORM, 'config'),
		]);
		$ilSelectInputGUI->setValue($row['value_admin']);
		$this->tpl->setVariable("VALUE_ADMIN", $ilSelectInputGUI->getToolbarHTML());

		self::dic()->ctrl()->setParameter($this->parent_obj, "xhfp_content", NULL);
	}


	/**
	 *
	 */
	protected function initData() {
		$this->setData(xoctSeriesWorkflowParameter::innerjoin(xoctWorkflowParameter::TABLE_NAME, 'param_id', 'id')->where(['obj_id' => $this->parent_obj->getObjId()])->getArray());
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
	 *
	 */
	protected function initTitle() {
	}
}