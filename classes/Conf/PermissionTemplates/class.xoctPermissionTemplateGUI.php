<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;

/**
 * Class xoctPermissionTemplateGUI
 *
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPermissionTemplateGUI: xoctMainGUI
 */
class xoctPermissionTemplateGUI extends xoctGUI
{
    public const IDENTIFIER = 'tpl_id';

    public const SUBTAB_GENERAL = 'general';
    public const SUBTAB_PERMISSION_TEMPLATES = 'permission_templates';

    public const CMD_UPDATE_TEMPLATE = 'updateTemplate';

    protected $subtab_active;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilLanguage
     */
    private $language;

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->language = $DIC->language();
    }

    public function executeCommand()
    {
        $this->ctrl->saveParameter($this, 'subtab_active');

        parent::executeCommand();
    }

    /**
     *
     */
    protected function index()
    {
        $this->setSubTabs();

        $this->subtab_active = $_GET['subtab_active'] ?: self::SUBTAB_GENERAL;
        $this->tabs->setSubTabActive($this->subtab_active);
        $this->ctrl->saveParameter($this, 'subtab_active');
        switch ($this->subtab_active) {
            case self::SUBTAB_GENERAL:
                $xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
                $xoctVideoPortalSettingsFormGUI->fillForm();
                $this->main_tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML());
                break;
            case self::SUBTAB_PERMISSION_TEMPLATES:
                $xoctPermissionTemplateTableGUI = new xoctPermissionTemplateTableGUI($this);
                $this->main_tpl->setContent($xoctPermissionTemplateTableGUI->getHTML());
                break;
        }
    }

    /**
     *
     */
    protected function add()
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this, new PermissionTemplate());
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    /**
     *
     */
    protected function create()
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this, new PermissionTemplate());
        $xoctPermissionTemplateFormGUI->setValuesByPost();
        if ($xoctPermissionTemplateFormGUI->saveForm()) {
            ilUtil::sendSuccess($this->plugin->txt('config_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    /**
     *
     */
    protected function edit()
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI(
            $this,
            PermissionTemplate::find($_GET[self::IDENTIFIER])
        );
        $xoctPermissionTemplateFormGUI->fillForm();
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    /**
     *
     */
    protected function update()
    {
        $xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
        $xoctVideoPortalSettingsFormGUI->setValuesByPost();
        if ($xoctVideoPortalSettingsFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->txt('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->main_tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML());
    }

    /**
     *
     */
    protected function updateTemplate()
    {
        $xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI(
            $this,
            PermissionTemplate::find($_GET[self::IDENTIFIER])
        );
        $xoctPermissionTemplateFormGUI->setValuesByPost();
        if ($xoctPermissionTemplateFormGUI->saveForm()) {
            ilUtil::sendSuccess($this->plugin->txt('config_msg_success'), true);
            $this->ctrl->redirect($this);
        }
        $this->main_tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
    }

    protected function setSubTabs()
    {
        $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_GENERAL);
        $this->tabs->addSubTab(
            self::SUBTAB_GENERAL,
            $this->plugin->txt('subtab_' . self::SUBTAB_GENERAL),
            $this->ctrl->getLinkTarget($this)
        );
        $this->ctrl->setParameter($this, 'subtab_active', self::SUBTAB_PERMISSION_TEMPLATES);
        $this->tabs->addSubTab(
            self::SUBTAB_PERMISSION_TEMPLATES,
            $this->plugin->txt('subtab_' . self::SUBTAB_PERMISSION_TEMPLATES),
            $this->ctrl->getLinkTarget($this)
        );
        $this->ctrl->clearParameters($this);
    }

    /**
     *
     */
    protected function confirmDelete()
    {
        $tpl_id = $_POST['tpl_id'];
        $template = PermissionTemplate::find($tpl_id);
        $template->delete();
        ilUtil::sendSuccess($this->plugin->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    protected function delete()
    {
        ilUtil::sendQuestion($this->plugin->txt('msg_confirm_delete_perm_template'));
        $tpl_id = $_GET['tpl_id'];
        $template = PermissionTemplate::find($tpl_id);
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->addItem('tpl_id', $tpl_id, $template->getTitle());
        $ilConfirmationGUI->addButton($this->language->txt('delete'), self::CMD_CONFIRM);
        $ilConfirmationGUI->addButton($this->language->txt('cancel'), self::CMD_STANDARD);
        $this->main_tpl->setContent($ilConfirmationGUI->getHTML());
    }

    /**
     * ajax
     */
    protected function reorder()
    {
        $ids = $_POST['ids'];
        $sort = 1;
        foreach ($ids as $id) {
            /** @var PermissionTemplate $perm_tpl */
            $perm_tpl = PermissionTemplate::find($id);
            $perm_tpl->setSort($sort);
            $perm_tpl->update();
            $sort++;
        }
        exit;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function txt($key)
    {
        return $this->plugin->txt('config_' . $key);
    }
}
