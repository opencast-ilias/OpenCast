<?php
use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
/**
 * Class xoctSeriesWorkflowParameterFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParameterFormGUI extends PropertyFormGUI {

	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const PROPERTY_TITLE = 'setTitle';

	/**
	 * @var xoctSeriesGUI
	 */
	protected $parent;

	protected function getValue($key) {
		// TODO: Implement getValue() method.
	}


	protected function initCommands() {
		$this->addCommandButton(xoctSeriesGUI::CMD_UPDATE_WORKFLOW_PARAMS, $this->lng->txt('save'));

	}


	protected function initFields() {
		$this->fields[] = [
			self::PROPERTY_CLASS => ilFormSectionHeaderGUI::class,
			self::PROPERTY_TITLE => self::plugin()->translate('workflow_parameters'),
		];
		/** @var xoctWorkflowParameter $xoctWorkflowParameter */
		foreach (xoctWorkflowParameter::innerjoin(
			xoctSeriesWorkflowParameter::TABLE_NAME,
			'id',
			'param_id',
			['value', 'id'])
			         ->where(['obj_id' => $this->parent->getObjId()])->get() as $xoctWorkflowParameter) {
			$this->fields[$xoctWorkflowParameter->xoct_series_param_id] = [
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_TITLE => $xoctWorkflowParameter->getTitle() ?: $xoctWorkflowParameter->getId(),
				self::PROPERTY_OPTIONS => [
					xoctWorkflowParameter::VALUE_IGNORE => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_IGNORE, 'config'),
					xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY, 'config'),
					xoctWorkflowParameter::VALUE_SHOW_IN_FORM => self::plugin()->translate('workflow_parameter_value_' . xoctWorkflowParameter::VALUE_SHOW_IN_FORM, 'config')
				],
				self::PROPERTY_VALUE => $xoctWorkflowParameter->xoct_series_param_value,
			];
		}
	}


	protected function initId() {
		// TODO: Implement initId() method.
	}


	protected function initTitle() {
		// TODO: Implement initTitle() method.
	}


	protected function storeValue($key, $value) {
		$xoctSeriesWorkflowParameter = xoctSeriesWorkflowParameter::find($key);
		$xoctSeriesWorkflowParameter->setValue($value);
		$xoctSeriesWorkflowParameter->update();
	}
}