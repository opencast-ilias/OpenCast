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
     * @var ilObjUser
     */
	protected $user;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;

	public function __construct(xoctPermissionTemplateGUI $a_parent_obj, $a_parent_cmd = "", $a_template_context = "") {
		global $DIC;
		$ilCtrl = $DIC['ilCtrl'];
		$tpl = $DIC['tpl'];
		$this->ctrl = $ilCtrl;
		$this->parent_obj = $a_parent_obj;
		$this->user = $DIC['ilUser'];
		$this->pl = ilOpenCastPlugin::getInstance();

		$this->setId('test');
		$this->setTitle($a_parent_obj->txt('permission_templates'));
		$this->setDescription($this->pl->txt('msg_permission_templates_info'));
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		$this->setEnableNumInfo(false);
		$this->setLimit(0);
		$this->setShowRowsSelector(false);

		$this->setRowTemplate($this->pl->getDirectory() . '/templates/default/tpl.permission_templates.html');

        $b = ilLinkButton::getInstance();
        $b->setCaption($this->pl->txt('button_new_permission_template'), false);
        $b->setUrl($this->ctrl->getLinkTarget($a_parent_obj, xoctPermissionTemplateGUI::CMD_ADD));
        $this->addCommandButtonInstance($b);

		xoctWaiterGUI::initJS();
		$tpl->addJavaScript($this->pl->getDirectory() . '/templates/default/sortable.js');
		$base_link = $this->ctrl->getLinkTarget($this->parent_obj, 'reorder', '', true);
		$tpl->addOnLoadCode("xoctSortable.init('" . $base_link . "');");

		$this->initColumns();
		$this->setData(xoctPermissionTemplate::orderBy('sort')->getArray());
	}

	protected function initColumns() {
		$this->addColumn("", "", "10px", true);
		$this->addColumn($this->pl->txt('table_column_default'));
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
	    $a_set['title'] = $this->user->getLanguage() == 'de' ? $a_set['title_de'] : $a_set['title_en'];
	    $a_set['info'] = $this->user->getLanguage() == 'de' ? $a_set['info_de'] : $a_set['info_en'];
		$a_set['actions'] = $this->buildActions($a_set);
		$a_set['default'] = $a_set['default'] ? 'ok' : 'not_ok';
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