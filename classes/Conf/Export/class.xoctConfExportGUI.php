<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;

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

	/**
	 *
	 */
	protected function import() {
		xoctConf::importFromXML($_FILES['xoct_import']['tmp_name']);
		$this->cancel();
	}

	/**
	 *
	 */
	protected function export() {
		// ob_end_clean();
		header('Content-Disposition: attachment; filename="opencastexport.xml"');
		echo xoctConf::getXMLExport();exit;
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
