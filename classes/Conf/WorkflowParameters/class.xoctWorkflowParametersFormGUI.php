<?php

use \srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;

/**
 * Class xoctWorkflowParametersFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParametersFormGUI extends PropertyFormGUI {

	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const PROPERTY_TITLE = 'setTitle';
	const PROPERTY_INFO = 'setInfo';

	const F_OVERWRITE_SERIES_PARAMS = 'overwrite_series_params';

	/**
	 * @var xoctWorkflowParameterGUI
	 */
	protected $parent;

	/**
	 * @param string $key
	 *
	 * @return mixed|void
	 */
	protected function getValue(string $key) {

	}

	protected function initCommands() : void
    {
		$this->addCommandButton(xoctWorkflowParameterGUI::CMD_UPDATE_FORM, self::dic()->language()->txt('save'));
	}


	protected function initFields() : void
    {
		$this->fields[xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES] = [
			self::PROPERTY_TITLE => self::plugin()->translate(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES, 'config'),
			self::PROPERTY_CLASS => ilCheckboxInputGUI::class,
			self::PROPERTY_VALUE => (bool) xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES),
			self::PROPERTY_SUBITEMS => [
				self::F_OVERWRITE_SERIES_PARAMS => [
					self::PROPERTY_TITLE => self::plugin()->translate(self::F_OVERWRITE_SERIES_PARAMS, 'config'),
					self::PROPERTY_INFO => self::plugin()->translate(self::F_OVERWRITE_SERIES_PARAMS . '_info', 'config'),
					self::PROPERTY_CLASS => ilCheckboxInputGUI::class,
				]
			]
		];
	}


	/**
	 *
	 */
	protected function initId() : void
    {
	}


	/**
	 *
	 */
	protected function initTitle() : void
    {
		$this->setTitle(self::plugin()->translate('settings', 'tab'));
	}


	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	protected function storeValue(string $key, $value) : void
    {
		switch ($key) {
			case xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES:
				xoctConf::set(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES, $value);
				break;
			case self::F_OVERWRITE_SERIES_PARAMS:
				if ($value == true) {
					$this->parent->setOverwriteSeriesParameter();
				}
				break;
			default:
				break;
		}
	}

}