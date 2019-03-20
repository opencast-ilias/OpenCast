<?php

/**
 * Class xoctWorkflowParameterRepository
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctWorkflowParameterRepository {

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
				$xoctWorkflowParameter->setType($input->getAttribute('type'));
			}
			$workflow_parameters[] = $xoctWorkflowParameter;
		}
		return $workflow_parameters;
	}
}