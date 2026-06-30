<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctPermissionTemplateTableGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateTableGUI extends ilTable2GUI
{
    use LocaleTrait;
    private ilOpenCastPlugin $plugin;
    private ilObjUser $user;
    private Factory $ui_factory;
    private Renderer $ui_renderer;

    public function __construct(xoctPermissionTemplateGUI $a_parent_obj, string $a_parent_cmd = "", string $a_template_context = "")
    {
        global $DIC;
        $opencastContainer = Init::init();
        $this->ctrl = $DIC->ctrl();
        $this->ui_factory = $opencastContainer->ilias()->ui()->factory();
        $this->ui_renderer = $opencastContainer->ilias()->ui()->renderer();
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
            'tpl.permission_templates.html',
            'public/Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/'
        );

        $b = ilLinkButton::getInstance();
        $b->setCaption($this->getLocaleString('button_new_permission_template'), false);
        $b->setUrl($this->ctrl->getLinkTarget($a_parent_obj, xoctGUI::CMD_ADD));
        $this->addCommandButtonInstance($b);

        new WaitOverlay($this->main_tpl); // TODO check if needed

        $this->main_tpl->addJavaScript($this->plugin->getRelativeDirectory() . '/templates/default/sortable.js');
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
        $this->addColumn($this->getLocaleString('actions', 'common'), "", '120px', false);
    }

    #[ReturnTypeWillChange]
    protected function fillRow(/*array*/ array $a_set): void
    {
        $a_set['title'] = $this->user->getLanguage() === 'de' ? $a_set['title_de'] : $a_set['title_en'];
        $a_set['info'] = $this->user->getLanguage() === 'de' ? $a_set['info_de'] : $a_set['info_en'];
        $a_set['actions'] = $this->buildActions($a_set);
        $a_set['default'] = $this->boolIconSrc((bool) $a_set['is_default']);
        $a_set['read'] = $this->boolIconSrc((bool) $a_set['read_access']);
        $a_set['write'] = $this->boolIconSrc((bool) $a_set['write_access']);
        parent::fillRow($a_set);
    }

    /**
     * Resolves the check-/cross-icon path via the ILIAS asset resolver instead
     * of a hard-coded template path (the standard images moved from
     * templates/default/images to assets/images in ILIAS 10).
     */
    private function boolIconSrc(bool $value): string
    {
        return ilUtil::getHtmlPath(
            ilUtil::getImagePath($value ? 'standard/icon_ok.svg' : 'standard/icon_not_ok.svg')
        );
    }

    protected function buildActions(array $a_set): string
    {
        $this->ctrl->setParameter($this->parent_obj, xoctPermissionTemplateGUI::IDENTIFIER, $a_set['id']);
        $dropdown = $this->ui_factory->dropdown()->standard(
            [
                $this->ui_factory->link()->standard(
                    $this->lng->txt('edit'),
                    $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_EDIT)
                ),
                $this->ui_factory->link()->standard(
                    $this->lng->txt('delete'),
                    $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_DELETE)
                )
            ]
        );

        return $this->ui_renderer->render($dropdown);
    }
}
