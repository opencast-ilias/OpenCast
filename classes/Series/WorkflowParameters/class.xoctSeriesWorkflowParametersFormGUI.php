<?php
use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
/**
 * Class xoctSeriesWorkflowParametersFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParametersFormGUI extends PropertyFormGUI {

	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const PROPERTY_TITLE = 'setTitle';

	/**
	 * @var xoctSeriesGUI
	 */
	protected $parent;


	/**
	 * @param string $key
	 *
	 * @return mixed|void
	 */
	protected function getValue($key) {
	}


	/**
	 *
	 */
	protected function initCommands() {
		$this->addCommandButton(xoctSeriesGUI::CMD_UPDATE_WORKFLOW_PARAMS, self::dic()->language()->txt('save'));

	}


	/**
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 */
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
				self::PROPERTY_OPTIONS => xoctWorkflowParameterRepository::getSelectionOptions(),
				self::PROPERTY_VALUE => $xoctWorkflowParameter->xoct_series_param_value,
			];
		}
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


	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	protected function storeValue($key, $value) {
		xoctSeriesWorkflowParameterRepository::getInstance()->updateById($key, $value);
	}
}