<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctPermissionTemplateTableGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateTableGUI extends ilTable2GUI {

	/**
	 * @var xoctPermissionTemplateGUI
	 */
	protected $parent_obj;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;

	public function __construct(xoctPermissionTemplateGUI $a_parent_obj, $a_parent_cmd = "", $a_template_context = "") {
		global $ilCtrl, $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent_obj = $a_parent_obj;
		$this->pl = ilOpenCastPlugin::getInstance();

		$this->setId('test');
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		$this->setEnableNumInfo(false);
		$this->setLimit(0);
		$this->setShowRowsSelector(false);

		$this->setRowTemplate($this->pl->getDirectory() . '/templates/default/tpl.permission_templates.html');

		xoctWaiterGUI::initJS();
		$tpl->addJavaScript($this->pl->getDirectory() . '/templates/default/sortable.js');
		$base_link = $this->ctrl->getLinkTarget($this->parent_obj, 'reorder', '', true);
		$tpl->addOnLoadCode("xoctSortable.init('" . $base_link . "');");

		$this->initColumns();
		$this->setData(xoctPermissionTemplate::orderBy('sort')->getArray());
	}

	protected function initColumns() {
		$this->addColumn("", "", "10px", true);
		$this->addColumn($this->pl->txt('table_column_title'));
		$this->addColumn($this->pl->txt('table_column_info'));
		$this->addColumn($this->pl->txt('table_column_role'));
		$this->addColumn($this->pl->txt('table_column_read'));
		$this->addColumn($this->pl->txt('table_column_write'));
		$this->addColumn($this->pl->txt('table_column_additional_acl_actions'));
		$this->addColumn($this->pl->txt('table_column_additional_actions_download'));
		$this->addColumn($this->pl->txt('table_column_additional_actions_annotate'));
		$this->addColumn("", "", '30px', true);
	}


	protected function fillRow($a_set) {
		$a_set['actions'] = $this->buildActions($a_set);
		$a_set['read'] = $a_set['read_access'] ? 'ok' : 'not_ok';
		$a_set['write'] = $a_set['write_access'] ? 'ok' : 'not_ok';
		parent::fillRow($a_set);
	}

	protected function buildActions($a_set) {
		$actions = new ilAdvancedSelectionListGUI();
		$actions->setListTitle($this->lng->txt('actions'));

		$this->ctrl->setParameter($this->parent_obj, xoctPermissionTemplateGUI::IDENTIFIER, $a_set['id']);
		$actions->addItem($this->lng->txt('edit'), '',$this->ctrl->getLinkTarget($this->parent_obj, xoctPermissionTemplateGUI::CMD_EDIT));
		$actions->addItem($this->lng->txt('delete'), '', $this->ctrl->getLinkTarget($this->parent_obj, xoctPermissionTemplateGUI::CMD_DELETE));

		return $actions->getHTML();
	}
}