<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctPermissionTemplateTableGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateTableGUI extends ilTable2GUI
{
    use LocaleTrait;
    /**
     * @var ilOpenCastPlugin
     */
    private $plugin;
    /**
     * @var \ilObjUser
     */
    private $user;

    public function __construct(xoctPermissionTemplateGUI $a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
    {
        global $DIC, $opencastContainer;
        $this->ctrl = $DIC->ctrl();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->user = $DIC->user();
        $this->parent_obj = $a_parent_obj;

        $this->setId('test');
        $this->setTitle($this->getLocaleString('permission_templates', 'config'));
        $this->setDescription($this->getLocaleString('msg_permission_templates_info'));
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $this->setEnableNumInfo(false);
        $this->setLimit(0);
        $this->setShowRowsSelector(false);

        $this->setRowTemplate(
            $this->plugin->getDirectory() . '/templates/default/tpl.permission_templates.html'
        );

        $b = ilLinkButton::getInstance();
        $b->setCaption($this->getLocaleString('button_new_permission_template'), false);
        $b->setUrl($this->ctrl->getLinkTarget($a_parent_obj, xoctGUI::CMD_ADD));
        $this->addCommandButtonInstance($b);

        new WaitOverlay($this->main_tpl); // TODO check if needed

        $this->main_tpl->addJavaScript($this->plugin->getDirectory() . '/templates/default/sortable.js');
        $base_link = $this->ctrl->getLinkTarget($this->parent_obj, 'reorder', '', true);
        $this->main_tpl->addOnLoadCode("xoctSortable.init('" . $base_link . "');");

        $this->initColumns();
        $this->setData(PermissionTemplate::orderBy('sort')->getArray());
    }

    protected function initColumns()
    {
        $this->addColumn("", "", "10px", true);
        $this->addColumn($this->getLocaleString('table_column_default'), "", "25px");
        $this->addColumn($this->getLocaleString('table_column_title'));
        $this->addColumn($this->getLocaleString('table_column_info'));
        $this->addColumn($this->getLocaleString('table_column_role'));
        $this->addColumn($this->getLocaleString('table_column_read'), "", "25px");
        $this->addColumn($this->getLocaleString('table_column_write'), "", "25px");
        $this->addColumn($this->getLocaleString('table_column_additional_acl_actions'));
        $this->addColumn($this->getLocaleString('table_column_additional_actions_download'));
        $this->addColumn($this->getLocaleString('table_column_additional_actions_annotate'));
        $this->addColumn($this->getLocaleString('actions', 'common'), "", '30px', false);
    }

    #[ReturnTypeWillChange]
    protected function fillRow(/*array*/ $a_set): void
    {
        $a_set['title'] = $this->user->getLanguage() == 'de' ? $a_set['title_de'] : $a_set['title_en'];
        $a_set['info'] = $this->user->getLanguage() == 'de' ? $a_set['info_de'] : $a_set['info_en'];
        $a_set['actions'] = $this->buildActions($a_set);
        $a_set['default'] = $a_set['is_default'] ? 'ok' : 'not_ok';
        $a_set['read'] = $a_set['read_access'] ? 'ok' : 'not_ok';
        $a_set['write'] = $a_set['write_access'] ? 'ok' : 'not_ok';
        parent::fillRow($a_set);
    }

    protected function buildActions(array $a_set): string
    {
        $actions = new ilAdvancedSelectionListGUI();
        $actions->setListTitle($this->getLocaleString('actions', 'common'));

        $this->ctrl->setParameter($this->parent_obj, xoctPermissionTemplateGUI::IDENTIFIER, $a_set['id']);
        $actions->addItem(
            $this->lng->txt('edit'),
            '',
            $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_EDIT)
        );
        $actions->addItem(
            $this->lng->txt('delete'),
            '',
            $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_DELETE)
        );

        return $actions->getHTML();
    }
}
