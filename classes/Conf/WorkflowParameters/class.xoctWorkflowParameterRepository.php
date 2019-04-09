<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * Class xoctWorkflowParameterRepository
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterRepository {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var self
	 */
	protected static $instance;
	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @return self
	 */
	public static function getInstance() {
		if (self::$instance == NULL) {
			$self = new self();
			self::$instance = $self;
		}
		return self::$instance;
	}


	/**
	 * @return array
	 * @throws xoctException
	 */
	public function loadParametersFromAPI() {
		xoctConf::setApiSettings();
		$workflow_id = xoctConf::getConfig(xoctConf::F_WORKFLOW);
		if (!$workflow_id) {
			throw new xoctException(xoctException::INTERNAL_ERROR, 'No Workflow defined in plugin configuration.');
		}
		$response = json_decode(xoctRequest::root()->workflowDefinition($workflow_id)->parameter('withconfigurationpanel', 'true')->get(), true);

		if ($response == false) {
			throw new xoctException(xoctException::INTERNAL_ERROR, "Couldn't fetch workflow information for workflow '$workflow_id'.");
		}

		if(!isset($response['configuration_panel'])) {
			throw new xoctException(xoctException::INTERNAL_ERROR, 'No configuration panel found for workflow with id = ' . $workflow_id);
		}

		return $this->parseConfigurationPanelHTML($response['configuration_panel']);
	}


	/**
	 * @param $configuration_panel_html
	 *
	 * @return array
	 */
	protected function parseConfigurationPanelHTML($configuration_panel_html) {
		$dom = new DOMDocument();
		$dom->strictErrorChecking = false;
		$configuration_panel_html = trim(str_replace("\n", "", $configuration_panel_html));
		$dom->loadHTML($configuration_panel_html);
		$inputs = $dom->getElementsByTagName('input');
		$labels = $dom->getElementsByTagName('label');
		$workflow_parameters = [];
		/** @var DOMElement $input */
		foreach ($inputs as $input) {
			/** @var xoctWorkflowParameter $xoctWorkflowParameter */
			$xoctWorkflowParameter = xoctWorkflowParameter::findOrGetInstance($input->getAttribute('id'));
			if (!$xoctWorkflowParameter->getTitle()) {
				$name = $input->getAttribute('name');
				/** @var DOMElement $label */
				foreach ($labels as $label) {
					if ($label->getAttribute('for') == $name) {
						$xoctWorkflowParameter->setTitle($label->nodeValue);
						break;
					}
				}
			}
			if (!$xoctWorkflowParameter->getType()) {
//				$xoctWorkflowParameter->setType($input->getAttribute('type'));  // for now, only checkbox is supported
				$xoctWorkflowParameter->setType(xoctWorkflowParameter::TYPE_CHECKBOX);
			}
			$workflow_parameters[] = $xoctWorkflowParameter;
		}
		return $workflow_parameters;
	}


	/**
	 * @param $ids int|int[] single or multiple
	 */
	public function deleteById($ids) {
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		foreach ($ids as $id) {
			xoctWorkflowParameter::find($id)->delete();
		}
		xoctSeriesWorkflowParameterRepository::getInstance()->deleteParamsForAllObjectsById($ids);
	}


	/**
	 * @param     $id
	 * @param     $title
	 * @param     $type
	 *
	 * @param int $default_value_member
	 * @param int $default_value_admin
	 *
	 * @return xoctWorkflowParameter
	 */
	public function createOrUpdate($id, $title, $type, $default_value_member = 0, $default_value_admin = 0) {
		if (!xoctWorkflowParameter::where(array('id' => $id))->hasSets()) {
			$is_new = true;
		}
		/** @var xoctWorkflowParameter $xoctWorkflowParameter */
		$xoctWorkflowParameter = xoctWorkflowParameter::findOrGetInstance($id);
		$xoctWorkflowParameter->setTitle($title);
		$xoctWorkflowParameter->setType($type);
		$xoctWorkflowParameter->setDefaultValueMember($default_value_member);
		$xoctWorkflowParameter->setDefaultValueAdmin($default_value_admin);
		$xoctWorkflowParameter->store();

		if ($is_new) {
			xoctSeriesWorkflowParameterRepository::getInstance()->createParamsForAllObjects($xoctWorkflowParameter);
		}

		return $xoctWorkflowParameter;
	}


	/**
	 *
	 */
	public function overwriteSeriesParameter() {
		/** @var xoctWorkflowParameter $xoctWorkflowParameter */
		foreach (xoctWorkflowParameter::get() as $xoctWorkflowParameter) {
			$sql = 'UPDATE ' . xoctSeriesWorkflowParameter::TABLE_NAME .
				' SET value_member = ' . self::dic()->database()->quote($xoctWorkflowParameter->getDefaultValueMember(), 'integer') . ', ' .
				' value_admin = ' . self::dic()->database()->quote($xoctWorkflowParameter->getDefaultValueAdmin(), 'integer') .
				' WHERE param_id = ' . self::dic()->database()->quote($xoctWorkflowParameter->getId(), 'text');
			self::dic()->database()->query($sql);
		}
	}


	/**
	 * @return array
	 * @throws \srag\DIC\OpenCast\Exception\DICException
	 */
	public static function getSelectionOptions() {
		$options = [];
		foreach (xoctWorkflowParameter::$possible_values as $value) {
			$options[$value] = self::plugin()->translate('workflow_parameter_value_' . $value, 'config');
		}
		return $options;
	}
}