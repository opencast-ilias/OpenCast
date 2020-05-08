<?php

use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage;

/**
 * Class xoctConfExportGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy xoctConfExportGUI : xoctMainGUI
 */
class xoctConfExportGUI extends xoctGUI {


	protected function index() {
		$b = ilLinkButton::getInstance();
		$b->setCaption('rep_robj_xoct_admin_export');
		$b->setUrl(self::dic()->ctrl()->getLinkTarget($this, 'export'));
		self::dic()->toolbar()->addButtonInstance($b);
		self::dic()->toolbar()->addSpacer();
		self::dic()->toolbar()->addSeparator();
		self::dic()->toolbar()->addSpacer();

		self::dic()->toolbar()->setFormAction(self::dic()->ctrl()->getLinkTarget($this, 'import'), true);
		$import = new ilFileInputGUI('xoct_import', 'xoct_import');
		self::dic()->toolbar()->addInputItem($import);
		self::dic()->toolbar()->addFormButton(self::plugin()->translate('admin_import'), 'import');
	}


	protected function import() {
		$domxml = new DOMDocument('1.0', 'UTF-8');
		$domxml->loadXML(file_get_contents($_FILES['xoct_import']['tmp_name']));

		/**
		 * @var $node DOMElement
		 */
		$xoct_confs = $domxml->getElementsByTagName('xoct_conf');
		foreach ($xoct_confs as $node) {
			$name = $node->getElementsByTagName('name')->item(0)->nodeValue;
			$value = $node->getElementsByTagName('value')->item(0)->nodeValue;
			if ($name) {
				$value = (is_array(json_decode($value))) ? json_decode($value) : $value;
				xoctConf::set($name, $value);
			}
		}

		/**
		 * @var $xoctWorkflowParameter xoctWorkflowParameter
		 */
		$xoct_workflow_parameter = $domxml->getElementsByTagName('xoct_workflow_parameter');

		foreach ($xoct_workflow_parameter as $node) {
			$id = $node->getElementsByTagName('id')->item(0)->nodeValue;
			if (!$id) {
				continue;
			}
			$xoctWorkflowParameter = xoctWorkflowParameter::findOrGetInstance($id);
			$xoctWorkflowParameter->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue);
			$xoctWorkflowParameter->setType($node->getElementsByTagName('type')->item(0)->nodeValue);
			$xoctWorkflowParameter->setDefaultValueMember($node->getElementsByTagName('default_value_member')->item(0)->nodeValue);
			$xoctWorkflowParameter->setDefaultValueAdmin($node->getElementsByTagName('default_value_admin')->item(0)->nodeValue);

			if (!xoctWorkflowParameter::where(array( 'id' => $xoctWorkflowParameter->getId() ))->hasSets()) {
				$xoctWorkflowParameter->create();
			} else {
				$xoctWorkflowParameter->update();
			}
		}

		/**
		 * @var $xoctPublicationUsage PublicationUsage
		 */
		$xoct_publication_usage = $domxml->getElementsByTagName('xoct_publication_usage');

		foreach ($xoct_publication_usage as $node) {
			$usage_id = $node->getElementsByTagName('usage_id')->item(0)->nodeValue;
			if (!$usage_id) {
				continue;
			}
			$xoctPublicationUsage = PublicationUsage::findOrGetInstance($usage_id);
			$xoctPublicationUsage->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue);
			$xoctPublicationUsage->setDescription($node->getElementsByTagName('description')->item(0)->nodeValue);
			$xoctPublicationUsage->setChannel($node->getElementsByTagName('channel')->item(0)->nodeValue);
			$xoctPublicationUsage->setFlavor($node->getElementsByTagName('flavor')->item(0)->nodeValue);
			$xoctPublicationUsage->setTag($node->getElementsByTagName('tag')->item(0)->nodeValue ?: '');
			$xoctPublicationUsage->setSearchKey($node->getElementsByTagName('search_key')->item(0)->nodeValue ?: 'flavor');
			$xoctPublicationUsage->setMdType($node->getElementsByTagName('md_type')->item(0)->nodeValue);

			if (!PublicationUsage::where(array('usage_id' => $xoctPublicationUsage->getUsageId() ))->hasSets()) {
				$xoctPublicationUsage->create();
			} else {
				$xoctPublicationUsage->update();
			}
		}
		$this->cancel();
	}


	protected function export() {
		$domxml = new DOMDocument('1.0', 'UTF-8');
		$domxml->preserveWhiteSpace = false;
		$domxml->formatOutput = true;
		$config = $domxml->appendChild(new DOMElement('opencast_settings'));

		$xml_info = $config->appendChild(new DOMElement('info'));
		$xml_info->appendChild(new DOMElement('plugin_version', self::plugin()->getPluginObject()->getVersion()));
		$xml_info->appendChild(new DOMElement('plugin_db_version', self::plugin()->getPluginObject()->getDBVersion()));
		$xml_info->appendChild(new DOMElement('config_version', xoctConf::getConfig(xoctConf::CONFIG_VERSION)));

		// xoctConf
		$xml_xoctConfs = $config->appendChild(new DOMElement('xoct_confs'));
		/**
		 * @var $xoctConf xoctConf
		 */
		foreach (xoctConf::getCollection()->get() as $xoctConf) {
			$xml_xoctConf = $xml_xoctConfs->appendChild(new DOMElement('xoct_conf'));
			$xml_xoctConf->appendChild(new DOMElement('name', $xoctConf->getName()));
			//			$xml_xoctConf->appendChild(new DOMElement('value'))->appendChild(new DOMCdataSection($xoctConf->getValue()));
			$value = xoctConf::getConfig($xoctConf->getName());
			$value = is_array($value) ? json_encode($value) : $value;
			$xml_xoctConf->appendChild(new DOMElement('value'))->appendChild(new DOMCdataSection($value));
		}

		// xoctWorkflowParameters
		$xml_xoctWorkflowParameters = $config->appendChild(new DOMElement('xoct_workflow_parameters'));
		/**
		 * @var $xoctWorkflowParameter xoctWorkflowParameter
		 */
		foreach (xoctWorkflowParameter::get() as $xoctWorkflowParameter) {
			$xml_xoctPU = $xml_xoctWorkflowParameters->appendChild(new DOMElement('xoct_workflow_parameter'));
			$xml_xoctPU->appendChild(new DOMElement('id'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getId()));
			$xml_xoctPU->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getTitle()));
			$xml_xoctPU->appendChild(new DOMElement('type'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getType()));
			$xml_xoctPU->appendChild(new DOMElement('default_value_member'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getDefaultValueMember()));
			$xml_xoctPU->appendChild(new DOMElement('default_value_admin'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getDefaultValueAdmin()));
		}

		// xoctPublicationUsages
		$xml_xoctPublicationUsages = $config->appendChild(new DOMElement('xoct_publication_usages'));
		/**
		 * @var $xoctPublicationUsage PublicationUsage
		 */
		foreach (PublicationUsage::get() as $xoctPublicationUsage) {
			$xml_xoctPU = $xml_xoctPublicationUsages->appendChild(new DOMElement('xoct_publication_usage'));
			$xml_xoctPU->appendChild(new DOMElement('usage_id'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getUsageId()));
			$xml_xoctPU->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getTitle()));
			$xml_xoctPU->appendChild(new DOMElement('description'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getDescription()));
			$xml_xoctPU->appendChild(new DOMElement('channel'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getChannel()));
			$xml_xoctPU->appendChild(new DOMElement('flavor'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getFlavor()));
			$xml_xoctPU->appendChild(new DOMElement('tag'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getTag()));
			$xml_xoctPU->appendChild(new DOMElement('search_key'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getSearchKey()));
			$xml_xoctPU->appendChild(new DOMElement('md_type'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getMdType()));
		}

		// ob_end_clean();
		header('Content-Disposition: attachment; filename="opencastexport.xml"');
		echo $domxml->saveXML();exit;
	}


	protected function add() {
	}


	protected function create() {
	}


	protected function edit() {
	}


	protected function update() {
	}


	protected function confirmDelete() {
	}


	protected function delete() {
	}
}

?>
