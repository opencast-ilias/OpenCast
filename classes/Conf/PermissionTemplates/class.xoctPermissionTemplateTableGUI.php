<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\DIC\OpencastObject\DICTrait;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;

/**
 * Class xoctPermissionTemplateTableGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateTableGUI extends ilTable2GUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpencastObjectPlugin::class;

	/**
	 * @var xoctPermissionTemplateGUI
	 */
	protected $parent_obj;

	public function __construct(xoctPermissionTemplateGUI $a_parent_obj, $a_parent_cmd = "", $a_template_context = "") {
		$this->parent_obj = $a_parent_obj;

		$this->setId('test');
		$this->setTitle($a_parent_obj->txt('permission_templates'));
		$this->setDescription(self::plugin()->getPluginObject()->txt('msg_permission_templates_info'));
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		$this->setEnableNumInfo(false);
		$this->setLimit(0);
		$this->setShowRowsSelector(false);

		$this->setRowTemplate(self::plugin()->getPluginObject()->getDirectory() . '/templates/default/tpl.permission_templates.html');

        $b = ilLinkButton::getInstance();
        $b->setCaption(self::plugin()->getPluginObject()->txt('button_new_permission_template'), false);
        $b->setUrl(self::dic()->ctrl()->getLinkTarget($a_parent_obj, xoctPermissionTemplateGUI::CMD_ADD));
        $this->addCommandButtonInstance($b);

		xoctWaiterGUI::initJS();
		self::dic()->ui()->mainTemplate()->addJavaScript(self::plugin()->getPluginObject()->getDirectory() . '/templates/default/sortable.js');
		$base_link = self::dic()->ctrl()->getLinkTarget($this->parent_obj, 'reorder', '', true);
		self::dic()->ui()->mainTemplate()->addOnLoadCode("xoctSortable.init('" . $base_link . "');");

		$this->initColumns();
		$this->setData(PermissionTemplate::orderBy('sort')->getArray());
	}

	protected function initColumns() {
		$this->addColumn("", "", "10px", true);
		$this->addColumn(self::plugin()->translate('table_column_default'));
		$this->addColumn(self::plugin()->translate('table_column_title'));
		$this->addColumn(self::plugin()->translate('table_column_info'));
		$this->addColumn(self::plugin()->translate('table_column_role'));
		$this->addColumn(self::plugin()->translate('table_column_read'));
		$this->addColumn(self::plugin()->translate('table_column_write'));
		$this->addColumn(self::plugin()->translate('table_column_additional_acl_actions'));
		$this->addColumn(self::plugin()->translate('table_column_additional_actions_download'));
		$this->addColumn(self::plugin()->translate('table_column_additional_actions_annotate'));
		$this->addColumn("", "", '30px', true);
	}


	protected function fillRow($a_set) {
	    $a_set['title'] = self::dic()->user()->getLanguage() == 'de' ? $a_set['title_de'] : $a_set['title_en'];
	    $a_set['info'] = self::dic()->user()->getLanguage() == 'de' ? $a_set['info_de'] : $a_set['info_en'];
		$a_set['actions'] = $this->buildActions($a_set);
		$a_set['default'] = $a_set['is_default'] ? 'ok' : 'not_ok';
		$a_set['read'] = $a_set['read_access'] ? 'ok' : 'not_ok';
		$a_set['write'] = $a_set['write_access'] ? 'ok' : 'not_ok';
		parent::fillRow($a_set);
	}

	protected function buildActions($a_set) {
		$actions = new ilAdvancedSelectionListGUI();
		$actions->setListTitle(self::dic()->language()->txt('actions'));

		self::dic()->ctrl()->setParameter($this->parent_obj, xoctPermissionTemplateGUI::IDENTIFIER, $a_set['id']);
		$actions->addItem(self::dic()->language()->txt('edit'), '',self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctPermissionTemplateGUI::CMD_EDIT));
		$actions->addItem(self::dic()->language()->txt('delete'), '', self::dic()->ctrl()->getLinkTarget($this->parent_obj, xoctPermissionTemplateGUI::CMD_DELETE));

		return $actions->getHTML();
	}
}
